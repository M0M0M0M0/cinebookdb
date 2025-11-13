<?php

namespace App\Http\Controllers;

use App\Models\Showtime;
use App\Models\Booking;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\DayModifier;
use App\Models\TimeSlotModifier;

class BookingController extends Controller
{
    public function validateBooking($booking_id)
    {
        try {
            $booking = Booking::find($booking_id);

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'is_valid' => false,
                    'message' => 'Booking not found'
                ], 404);
            }

            // Kiểm tra booking có thuộc user hiện tại không
            if ((string)$booking->web_user_id !== (string)auth()->id()) {
                return response()->json([
                    'success' => false,
                    'is_valid' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Kiểm tra booking đã hết hạn chưa
            $isExpired = now()->greaterThan($booking->expires_at);

            return response()->json([
                'success' => true,
                'is_valid' => !$isExpired && $booking->status === 'pending',
                'status' => $booking->status,
                'expired' => $isExpired,
                'expires_at' => $booking->expires_at
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'is_valid' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    protected function getDayModifierForShowtime(Showtime $showtime)
    {
        // Lấy ngày trong tuần (0 = Sunday, 6 = Saturday)
        $dayOfWeekNumber = $showtime->start_time->dayOfWeek;

        // Xác định Weekday (Mon-Fri) hay Weekend (Sat-Sun)
        $dayType = in_array($dayOfWeekNumber, [0, 6]) ? 'Weekend' : 'Weekday';

        $modifier = DayModifier::where('day_type', $dayType)
                               ->where('is_active', 1)
                               ->first();

        if (!$modifier) {
            return [
                'id' => null,
                'multiplier' => 1.0,
                'amount' => 0,
            ];
        }

        // Tính multiplier dựa trên modifier_type và operation
        $multiplierValue = 1.0;
        $amount = (float)$modifier->modifier_amount;

        if ($modifier->operation === 'increase') {
            if ($modifier->modifier_type === 'percent') {
                // Nếu modifier_amount = 20 và type = percent => +20% => multiplier = 1.2
                $multiplierValue = 1.0 + ($amount / 100);
            } elseif ($modifier->modifier_type === 'fixed') {
                // Fixed amount sẽ cộng trực tiếp vào giá, không dùng multiplier
                // Trả về thông tin để xử lý riêng
                return [
                    'id' => $modifier->day_modifier_id,
                    'multiplier' => 1.0,
                    'fixed_amount' => $amount,
                    'type' => 'fixed',
                ];
            }
        } elseif ($modifier->operation === 'decrease') {
            if ($modifier->modifier_type === 'percent') {
                // Giảm giá % (ít dùng nhưng để phòng xa)
                $multiplierValue = 1.0 - ($amount / 100);
            } elseif ($modifier->modifier_type === 'fixed') {
                return [
                    'id' => $modifier->day_modifier_id,
                    'multiplier' => 1.0,
                    'fixed_amount' => -$amount, // Âm để trừ
                    'type' => 'fixed',
                ];
            }
        }

        return [
            'id' => $modifier->day_modifier_id,
            'multiplier' => $multiplierValue,
            'fixed_amount' => 0,
            'type' => 'percent',
        ];
    }

    protected function getTimeSlotModifierForShowtime(Showtime $showtime)
    {
        $startTime = $showtime->start_time->format('H:i:s');

        $modifier = TimeSlotModifier::where('ts_start_time', '<=', $startTime)
                                    ->where('ts_end_time', '>', $startTime)
                                    ->where('is_active', 1)
                                    ->first();

        if (!$modifier) {
            return [
                'id' => null,
                'multiplier' => 1.0,
                'fixed_amount' => 0,
            ];
        }

        $multiplierValue = 1.0;
        $amount = (float)$modifier->ts_amount;

        if ($modifier->operation === 'increase') {
            if ($modifier->modifier_type === 'percent') {
                $multiplierValue = 1.0 + ($amount / 100);
            } elseif ($modifier->modifier_type === 'fixed') {
                return [
                    'id' => $modifier->time_slot_modifier_id,
                    'multiplier' => 1.0,
                    'fixed_amount' => $amount,
                    'type' => 'fixed',
                ];
            }
        } elseif ($modifier->operation === 'decrease') {
            if ($modifier->modifier_type === 'percent') {
                $multiplierValue = 1.0 - ($amount / 100);
            } elseif ($modifier->modifier_type === 'fixed') {
                return [
                    'id' => $modifier->time_slot_modifier_id,
                    'multiplier' => 1.0,
                    'fixed_amount' => -$amount,
                    'type' => 'fixed',
                ];
            }
        }

        return [
            'id' => $modifier->time_slot_modifier_id,
            'multiplier' => $multiplierValue,
            'fixed_amount' => 0,
            'type' => 'percent',
        ];
    }

    // -------------------------------
    // step 1: HOLD SEATS (pending booking)
    // -------------------------------
    public function holdSeats(Request $request)
    {
        $request->validate([
            'showtime_id' => 'required|exists:showtimes,showtime_id',
            'seat_codes'  => 'required|array|min:1'
        ]);

        $webUserId   = auth()->id();
        $showtimeId  = $request->showtime_id;
        $seatCodes   = $request->seat_codes;

        // kiểm tra ghế đã được giữ của booking pending chưa hết hạn
        $reserved = $this->getSoldSeats($showtimeId)->original['data']['reserved_seats'];
        $reserved = collect($reserved)->pluck('code')->toArray();

        if (count(array_intersect($seatCodes, $reserved)) > 0) {
            return response()->json(['success' => false,'message' => 'Some seats are already reserved'], 400);
        }

        // tạo booking pending
        $booking = Booking::create([
            'web_user_id' => auth()->id(),
            'showtime_id' => $showtimeId,
            'status'      => 'pending',
            'expires_at'  => now()->addMinutes(15), // Thời gian hết hạn 15 phút
            'seats_snapshot' => json_encode($seatCodes),
        ]);

        return response()->json([
            'success'    => true,
            'booking_id' => $booking->booking_id,
            'message'    => 'Seats are now reserved for 15 minutes'
        ]);
    }

    // ------------------------------------
    // step 2: FINALIZE (payment success)
    // ------------------------------------
    public function finalizePayment(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,booking_id',
            'seat_codes' => 'nullable|array' // Optional: frontend có thể gửi để double-check
        ]);

        $booking = Booking::with('showtime')->find($request->booking_id);

        // ✅ 1. VALIDATE BOOKING
        if (!$booking || $booking->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Booking or already completed'
            ], 400);
        }

        // ✅ 2. CHECK AUTHORIZATION
        if ((string)$booking->web_user_id !== (string)auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // ✅ 3. CHECK EXPIRATION
        if (now()->greaterThan($booking->expires_at)) {
            $booking->delete();
            return response()->json([
                'success' => false,
                'message' => 'Booking expired'
            ], 410);
        }

        // ✅ 4. CHECK SHOWTIME EXISTS
        if (!$booking->showtime) {
            return response()->json([
                'success' => false,
                'message' => 'Showtime not found'
            ], 404);
        }

        DB::beginTransaction();

        try {
            $showtime = $booking->showtime;
            $basePrice = $showtime->base_price;

            // ✅ 5. GET MODIFIERS
            $dayModifier  = $this->getDayModifierForShowtime($showtime);
            $timeModifier = $this->getTimeSlotModifierForShowtime($showtime);

            // ✅ 6. GET SEAT CODES
            $seatCodes = json_decode($booking->seats_snapshot, true);

            // Frontend có thể gửi seat_codes để double-check
            if ($request->has('seat_codes') && is_array($request->seat_codes)) {
                $seatCodes = $request->seat_codes;
                // Update lại snapshot nếu khác
                $booking->seats_snapshot = json_encode($seatCodes);
            }

            if (empty($seatCodes) || !is_array($seatCodes)) {
                throw new \Exception('No seat codes found in booking');
            }

            // ✅ 7. VALIDATE SEATS ARE STILL AVAILABLE
            // Kiểm tra xem có booking khác đã book các ghế này không
            $conflictingBookings = DB::table('bookings')
                ->where('showtime_id', $booking->showtime_id)
                ->where('booking_id', '!=', $booking->booking_id)
                ->where(function ($q) {
                    $q->where('status', 'completed')
                        ->orWhere(function ($qq) {
                            $qq->where('status', 'pending')
                                ->where('expires_at', '>', now());
                        });
                })
                ->get();

            $reservedSeats = [];
            foreach ($conflictingBookings as $b) {
                $seats = json_decode($b->seats_snapshot, true) ?? [];
                $reservedSeats = array_merge($reservedSeats, $seats);
            }

            $conflicts = array_intersect($seatCodes, $reservedSeats);
            if (count($conflicts) > 0) {
                throw new \Exception('Some seats are no longer available: ' . implode(', ', $conflicts));
            }

            // ✅ 8. GET SEAT DATA FROM DATABASE
            $seatsData = DB::table('seats as s')
                ->join('seat_types as st', 's.seat_type_id', '=', 'st.seat_type_id')
                ->where('s.room_id', $showtime->room_id)
                ->whereIn(DB::raw("CONCAT(s.seat_row, s.seat_number)"), $seatCodes)
                ->select(
                    's.seat_id',
                    's.seat_row',
                    's.seat_number',
                    's.seat_type_id',
                    'st.seat_type_name',
                    'st.seat_type_price'
                )
                ->get();

            if ($seatsData->count() !== count($seatCodes)) {
                throw new \Exception('Some seats not found in database');
            }

            // ✅ 9. CREATE TICKETS AND CALCULATE TOTAL
            $total = 0;
            $ticketsCreated = [];

            foreach ($seatsData as $s) {
                // Check for custom seat price for this showtime
                $customPrice = DB::table('showtime_seat_type_prices')
                    ->where('showtime_id', $booking->showtime_id)
                    ->where('seat_type_id', $s->seat_type_id)
                    ->where('is_active', 1)
                    ->value('custom_seat_price');

                $seatTypePrice = $customPrice ?? $s->seat_type_price;

                // Base price + seat type price
                $priceBeforeModifiers = $basePrice + $seatTypePrice;

                // Apply Day Modifier
                if (isset($dayModifier['type']) && $dayModifier['type'] === 'fixed') {
                    $priceAfterDay = $priceBeforeModifiers + $dayModifier['fixed_amount'];
                    $dayModifierValue = $dayModifier['fixed_amount'];
                } else {
                    $priceAfterDay = $priceBeforeModifiers * $dayModifier['multiplier'];
                    $dayModifierValue = $dayModifier['multiplier'];
                }

                // Apply Time Slot Modifier
                if (isset($timeModifier['type']) && $timeModifier['type'] === 'fixed') {
                    $finalPrice = $priceAfterDay + $timeModifier['fixed_amount'];
                    $timeModifierValue = $timeModifier['fixed_amount'];
                } else {
                    $finalPrice = $priceAfterDay * $timeModifier['multiplier'];
                    $timeModifierValue = $timeModifier['multiplier'];
                }

                $finalPrice = round($finalPrice, 2);
                $total += $finalPrice;

                // ✅ CREATE TICKET
                $ticket = Ticket::create([
                    'booking_id' => $booking->booking_id,
                    'seat_id' => $s->seat_id,
                    'base_price_snapshot' => $basePrice,
                    'seat_type_id_snapshot' => $s->seat_type_id,
                    'seat_type_price_snapshot' => $seatTypePrice,
                    'day_modifier_id_snapshot' => $dayModifier['id'],
                    'day_modifier_snapshot' => $dayModifierValue,
                    'time_slot_modifier_id_snapshot' => $timeModifier['id'],
                    'time_slot_modifier_snapshot' => $timeModifierValue,
                    'final_ticket_price' => $finalPrice
                ]);

                $ticketsCreated[] = [
                    'ticket_id' => $ticket->ticket_id,
                    'seat_code' => $s->seat_row . $s->seat_number,
                    'seat_type' => $s->seat_type_name,
                    'price' => $finalPrice
                ];
            }

            // ✅ 10. UPDATE BOOKING STATUS TO COMPLETED
            $booking->status = 'completed';
            $booking->save();

            DB::commit();

            // ✅ 11. RETURN SUCCESS RESPONSE
            return response()->json([
                'success' => true,
                'message' => 'Payment completed successfully',
                'data' => [
                    'booking_id' => $booking->booking_id,
                    'total_amount' => round($total, 2),
                    'tickets' => $ticketsCreated,
                    'seat_count' => count($ticketsCreated)
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Finalize payment error:', [
                'booking_id' => $booking->booking_id ?? null,
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Lấy thông tin tickets của một booking
     */
    public function getBookingTickets($booking_id)
    {
        $booking = Booking::with(['showtime.movie', 'tickets.seat.seatType'])
            ->find($booking_id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        // Check authorization
        if ((string)$booking->web_user_id !== (string)auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $tickets = $booking->tickets->map(function ($ticket) {
            return [
                'ticket_id' => $ticket->ticket_id,
                'seat_code' => $ticket->seat->seat_row . $ticket->seat->seat_number,
                'seat_type' => $ticket->seat->seatType->seat_type_name ?? 'Unknown',
                'final_price' => $ticket->final_ticket_price,
                'base_price' => $ticket->base_price_snapshot,
                'seat_type_price' => $ticket->seat_type_price_snapshot,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'booking_id' => $booking->booking_id,
                'status' => $booking->status,
                'movie_title' => $booking->showtime->movie->title ?? 'Unknown',
                'showtime' => $booking->showtime->start_time->format('d/m/Y H:i'),
                'tickets' => $tickets,
                'total_amount' => $tickets->sum('final_price'),
                'created_at' => $booking->created_at->toISOString(),
            ]
        ]);
    }

    // -------------------------------------------------
    // GET SOLD seats (return cả pending còn hạn & completed)
    // -------------------------------------------------
    public function getSoldSeats($showtime_id)
    {
        $showtime = Showtime::find($showtime_id);
        if (!$showtime) {
            return response()->json([
                'success' => false,
                'message' => 'Showtime not found'
            ], 404);
        }

        // 1️⃣ LẤY MODIFIERS
        $dayModifier = $this->getDayModifierForShowtime($showtime);
        $timeModifier = $this->getTimeSlotModifierForShowtime($showtime);

        // 2️⃣ LẤY GHẾ ĐÃ BÁN/RESERVED
        $bookings = DB::table('bookings')
            ->where('showtime_id', $showtime_id)
            ->where(function ($q) {
                $q->where('status', 'completed')
                    ->orWhere(function ($qq) {
                        $qq->where('status', 'pending')
                            ->where('expires_at', '>', now());
                    });
            })
            ->select('seats_snapshot', 'web_user_id', 'booking_id')

            ->get();

        $soldSeats = [];
        foreach ($bookings as $booking) {
            if ($booking->seats_snapshot) {
                $seats = json_decode($booking->seats_snapshot, true);
                if (is_array($seats)) {
                    foreach ($seats as $seat) {
                        $code = is_string($seat) ? $seat : ($seat['code'] ?? null);
                        if ($code) {
                            $soldSeats[] = [
                                'code' => $code,
                                'status' => 'sold',
                                'web_user_id' => $booking->web_user_id,
                                'booking_id' => $booking->booking_id,
                            ];
                        }
                    }
                }
            }
        }


        // Remove duplicates
        $soldSeats = array_values(array_unique($soldSeats, SORT_REGULAR));

        // 3️⃣ LẤY DANH SÁCH SEAT TYPES VÀ TÍNH GIÁ
        $seatTypes = DB::table('seat_types')
            ->select('seat_type_id', 'seat_type_name', 'seat_type_price')
            ->get();

        $seatTypePrices = [];

        foreach ($seatTypes as $seatType) {
            // 3.1: Kiểm tra có custom price cho showtime này không
            $customPrice = DB::table('showtime_seat_type_prices')
                ->where('showtime_id', $showtime_id)
                ->where('seat_type_id', $seatType->seat_type_id)
                ->where('is_active', 1)
                ->value('custom_seat_price');

            // 3.2: Nếu có custom price thì dùng, không thì dùng giá mặc định
            $baseSeatPrice = $customPrice ?? $seatType->seat_type_price;

            // 3.3: Tính giá cuối cùng với modifiers
            $priceBeforeModifiers = (float)$showtime->base_price + (float)$baseSeatPrice;

            // ✅ Áp dụng Day Modifier
            if (isset($dayModifier['type']) && $dayModifier['type'] === 'fixed') {
                // Fixed amount: cộng trực tiếp
                $priceAfterDay = $priceBeforeModifiers + $dayModifier['fixed_amount'];
            } else {
                // Percent: nhân với multiplier
                $priceAfterDay = $priceBeforeModifiers * $dayModifier['multiplier'];
            }

            // ✅ Áp dụng Time Slot Modifier
            if (isset($timeModifier['type']) && $timeModifier['type'] === 'fixed') {
                $finalPrice = $priceAfterDay + $timeModifier['fixed_amount'];
            } else {
                $finalPrice = $priceAfterDay * $timeModifier['multiplier'];
            }

            // 3.4: Lưu vào array
            $seatTypePrices[$seatType->seat_type_name] = [
                'seat_type_price' => round($finalPrice, 2),
                'base_seat_type_price' => (float)$baseSeatPrice,
                'has_custom_price' => $customPrice !== null,
            ];
        }

        // 4️⃣ TRẢ VỀ DỮ LIỆU
        return response()->json([
            'success' => true,
            'data' => [
                'base_showtime_price' => (float)$showtime->base_price,
                'reserved_seats' => $soldSeats,
                'seat_type_prices' => $seatTypePrices,
                'modifiers' => [
                    'day_modifier' => $dayModifier,
                    'time_modifier' => $timeModifier,
                ]
            ]
        ]);
    }

    // =============================
    // 2) CREATE BOOKING (LOCK SEAT)
    // =============================
    public function create(Request $request)
    {
        $request->validate([
            'showtime_id' => 'required|exists:showtimes,showtime_id',
            'seats'       => 'required|array|min:1'
        ]);

        $booking = Booking::create([
            'showtime_id' => $request->showtime_id,
            'status'      => 'pending',
            'expires_at'  => now()->addMinutes(15)
        ]);

        foreach ($request->seats as $seatCode) {
            $row = substr($seatCode, 0, 1);
            $num = substr($seatCode, 1);

            $seat_id = Seat::where('seat_row', $row)
                            ->where('seat_number', $num)
                            ->value('seat_id');

            if ($seat_id) {
                Ticket::create([
                    'booking_id' => $booking->booking_id,
                    'seat_id'    => $seat_id
                ]);
            }
        }

        return response()->json([
            'success'     => true,
            'booking_id'  => $booking->booking_id
        ], 201);
    }
    // ================================
    // 3) ADD FOODS TO BOOKING
    /**
 * Thêm/Cập nhật foods vào booking
 */
    public function addFoods(Request $request, $booking_id)
    {
        $request->validate([
            'foods' => 'nullable|array',
            'foods.*.food_id' => 'nullable|exists:foods,food_id',
            'foods.*.food_name' => 'required|string',
            'foods.*.quantity' => 'required|integer|min:1',
            'foods.*.price' => 'required|numeric|min:0', // ✅ VALIDATE PRICE
        ]);

        $booking = Booking::find($booking_id);

        // Kiểm tra booking hợp lệ và còn hạn
        if (!$booking || $booking->status !== 'pending' || now()->greaterThan($booking->expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired Booking'
            ], 400);
        }

        // ✅ LƯU FOODS VỚI ĐẦY ĐỦ THÔNG TIN (bao gồm price)
        $foodsData = $request->foods ?? [];

        // Đảm bảo mỗi food item có đầy đủ thông tin
        $processedFoods = array_map(function ($food) {
            return [
                'food_id' => $food['food_id'] ?? null,
                'food_name' => $food['food_name'],
                'quantity' => (int)$food['quantity'],
                'price' => (float)$food['price'],
            ];
        }, $foodsData);

        // Cập nhật snapshot đồ ăn
        $booking->foods_snapshot = json_encode($processedFoods);
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => 'Food snapshot updated'
        ]);
    }
    /**
 * Kiểm tra xem user có booking pending nào cho showtime này không
 */
    public function checkPendingBooking(Request $request)
    {
        $request->validate([
            'showtime_id' => 'required|exists:showtimes,showtime_id',
        ]);

        $userId = auth()->id();
        $showtimeId = $request->showtime_id;

        // Tìm booking pending còn hạn của user cho showtime này
        $pendingBooking = Booking::where('web_user_id', $userId)
            ->where('showtime_id', $showtimeId)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$pendingBooking) {
            return response()->json([
                'success' => true,
                'has_pending' => false,
            ]);
        }

        // Decode seats snapshot
        $seats = json_decode($pendingBooking->seats_snapshot, true) ?? [];

        return response()->json([
            'success' => true,
            'has_pending' => true,
            'booking' => [
                'booking_id' => $pendingBooking->booking_id,
                'seats' => $seats,
                'expires_at' => $pendingBooking->expires_at->toISOString(),
                'time_remaining' => now()->diffInSeconds($pendingBooking->expires_at, false), // Giây còn lại
            ],
        ]);
    }
    /**
 * Hủy booking pending
 */
    public function cancelBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,booking_id',
        ]);

        $userId = auth()->id();
        $booking = Booking::find($request->booking_id);

        // Kiểm tra quyền sở hữu
        if (!$booking || $booking->web_user_id != $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found or unauthorized',
            ], 403);
        }

        // Chỉ hủy được booking pending
        if ($booking->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel completed booking',
            ], 400);
        }

        // Xóa booking (hoặc update status = 'cancelled')
        $booking->delete();
        // Hoặc: $booking->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully',
        ]);
    }
    /**
 * Cập nhật ghế cho booking pending (thay vì tạo booking mới)
 */
    public function updateSeats(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,booking_id',
            'seat_codes' => 'required|array|min:1',
        ]);

        $userId = auth()->id();
        $booking = Booking::find($request->booking_id);

        // Kiểm tra quyền sở hữu
        if (!$booking || $booking->web_user_id != $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found or unauthorized',
            ], 403);
        }

        // Chỉ update được booking pending còn hạn
        if ($booking->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update completed booking',
            ], 400);
        }

        if (now()->greaterThan($booking->expires_at)) {
            $booking->delete();
            return response()->json([
                'success' => false,
                'message' => 'Booking expired',
            ], 410);
        }

        $showtimeId = $booking->showtime_id;
        $newSeatCodes = $request->seat_codes;

        // Lấy ghế cũ của booking này
        $oldSeats = json_decode($booking->seats_snapshot, true) ?? [];

        // Kiểm tra ghế mới có bị reserved bởi NGƯỜI KHÁC không
        $reservedByOthers = DB::table('bookings')
            ->where('showtime_id', $showtimeId)
            ->where('booking_id', '!=', $booking->booking_id) // ✅ Loại trừ booking hiện tại
            ->where(function ($q) {
                $q->where('status', 'completed')
                    ->orWhere(function ($qq) {
                        $qq->where('status', 'pending')
                            ->where('expires_at', '>', now());
                    });
            })
            ->get()
            ->flatMap(function ($b) {
                return json_decode($b->seats_snapshot, true) ?? [];
            })
            ->toArray();

        // Kiểm tra conflict
        $conflicts = array_intersect($newSeatCodes, $reservedByOthers);
        if (count($conflicts) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Some seats are reserved by others: ' . implode(', ', $conflicts),
            ], 400);
        }

        // ✅ Update seats snapshot
        $booking->seats_snapshot = json_encode($newSeatCodes);
        $booking->expires_at = now()->addMinutes(15); // Reset thời gian hết hạn
        $booking->save();

        return response()->json([
            'success' => true,
            'booking_id' => $booking->booking_id,
            'message' => 'Seats updated successfully',
        ]);
    }
    /**
 * Kiểm tra tất cả các booking pending của user (không giới hạn showtime)
 * Dùng cho global dialog check khi user đăng nhập
 */
    public function checkPendingAll(Request $request)
    {
        $userId = auth()->id();

        // Tìm booking pending còn hạn bất kỳ của user
        $pendingBooking = Booking::where('web_user_id', $userId)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->with(['showtime.movie']) // Eager load để lấy thông tin movie
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$pendingBooking) {
            return response()->json([
                'success' => true,
                'has_pending' => false,
            ]);
        }

        // Decode seats snapshot
        $seats = json_decode($pendingBooking->seats_snapshot, true) ?? [];

        // Lấy thông tin movie và showtime
        $movie = $pendingBooking->showtime->movie ?? null;
        $showtime = $pendingBooking->showtime;

        return response()->json([
            'success' => true,
            'has_pending' => true,
            'booking' => [
                'booking_id' => $pendingBooking->booking_id,
                'movie_id' => $movie ? $movie->movie_id : null,
                'movie_title' => $movie ? $movie->title : 'Unknown Movie',
                'showtime_id' => $pendingBooking->showtime_id,
                'showtime_display' => $showtime ? $showtime->start_time->format('d/m/Y H:i') : '',
                'seats' => $seats,
                'expires_at' => $pendingBooking->expires_at->toISOString(),
                'time_remaining' => now()->diffInSeconds($pendingBooking->expires_at, false), // Giây còn lại
            ],
        ]);
    }
    /**
 * Lấy tất cả bookings của user (cả pending và completed)
 */
    public function getUserBookings(Request $request)
    {
        $userId = auth()->id();

        // Lấy bookings với eager loading
        $bookings = Booking::where('web_user_id', $userId)
            ->with([
                'showtime.movie',
                'showtime.room.theater',
                'tickets.seat.seatType'
            ])
            ->whereHas('showtime')
            ->orderBy('created_at', 'desc')
            ->get();

        \Log::info('Total bookings found: ' . $bookings->count());

        // Phân loại bookings
        $currentBookings = [];
        $historyBookings = [];

        foreach ($bookings as $booking) {
            try {
                \Log::info('Processing booking: ' . $booking->booking_id);

                // ✅ Debug từng bước
                $showtime = $booking->showtime;
                \Log::info('Showtime loaded', ['showtime_id' => $showtime?->showtime_id]);

                if (!$showtime) {
                    \Log::error('Showtime is null for booking: ' . $booking->booking_id);
                    continue;
                }

                // ✅ Kiểm tra start_time
                if (!$showtime->start_time) {
                    \Log::error('start_time is null', [
                        'booking_id' => $booking->booking_id,
                        'showtime_id' => $showtime->showtime_id,
                        'showtime_data' => $showtime->toArray()
                    ]);
                    continue;
                }

                \Log::info('start_time value: ' . $showtime->start_time);

                // Lấy thông tin movie và showtime
                $movie = $showtime->movie ?? null;
                $theater = $showtime->room->theater ?? null;

                // Parse seats từ snapshot
                $seatCodes = json_decode($booking->seats_snapshot, true) ?? [];

                // Lấy thông tin tickets
                $tickets = $booking->tickets->map(function ($ticket) {
                    return [
                        'ticket_id' => $ticket->ticket_id,
                        'seat_code' => $ticket->seat->seat_row . $ticket->seat->seat_number,
                        'seat_type' => $ticket->seat->seatType->seat_type_name ?? 'Unknown',
                        'price' => $ticket->final_ticket_price,
                    ];
                });

                // Parse foods từ snapshot
                $foods = json_decode($booking->foods_snapshot, true) ?? [];
                $foodTotal = 0;

                if (!empty($foods)) {
                    foreach ($foods as $food) {
                        $price = (float)($food['price'] ?? 0);
                        $quantity = (int)($food['quantity'] ?? 0);
                        $foodTotal += $price * $quantity;
                    }
                }

                // Tính tổng tiền từ tickets
                $ticketTotal = $tickets->sum('price');
                $grandTotal = $ticketTotal + $foodTotal;

                // ✅ Kiểm tra lại trước khi dùng start_time
                $isPast = $showtime->start_time->isPast();
                $isExpired = $booking->status === 'pending' && now()->greaterThan($booking->expires_at);

                $bookingData = [
                    'booking_id' => $booking->booking_id,
                    'showtime_id' => $showtime->showtime_id,
                    'movie_id' => $movie ? $movie->movie_id : null,
                    'movie_title' => $movie ? $movie->title : 'Unknown',
                    'poster' => $movie ? $movie->poster_path : null,
                    'theater_name' => $theater ? $theater->theater_name : 'Unknown',
                    'theater_address' => $theater ? $theater->address : 'N/A',
                    'room_name' => $showtime->room->room_name ?? 'N/A',
                    'showtime_date' => $showtime->start_time->format('Y-m-d'),
                    'showtime_time' => $showtime->start_time->format('H:i'),
                    'showtime_full' => $showtime->start_time->format('d/m/Y H:i'),
                    'duration' => $movie ? $movie->duration . ' minutes' : 'N/A',
                    'language' => $movie ? ($movie->language ?? 'English') : 'N/A',
                    'format' => '2D',
                    'seats' => $seatCodes,
                    'seat_types' => $tickets->pluck('seat_type')->unique()->values(),
                    'tickets' => $tickets,
                    'foods' => $foods,
                    'ticket_total' => round($ticketTotal, 2),
                    'food_total' => round($foodTotal, 2),
                    'grand_total' => round($grandTotal, 2),
                    'status' => $booking->status,
                    'payment_method' => 'Card',
                    'created_at' => $booking->created_at->format('d/m/Y H:i'),
                    'expires_at' => $booking->expires_at ? $booking->expires_at->format('d/m/Y H:i') : null,
                    'is_expired' => $isExpired,
                    'is_past' => $isPast,
                    'next_step' => empty($foods) ? 'food' : 'payment',
                    'has_foods' => !empty($foods),
                ];

                // Phân loại
                if ($booking->status === 'completed' && $isPast) {
                    $historyBookings[] = $bookingData;
                } elseif ($booking->status === 'completed' && !$isPast) {
                    $currentBookings[] = $bookingData;
                } elseif ($booking->status === 'pending' && !$isExpired) {
                    $currentBookings[] = $bookingData;
                }

            } catch (\Exception $e) {
                \Log::error('Error processing booking', [
                    'booking_id' => $booking->booking_id,
                    'error' => $e->getMessage(),
                    'line' => $e->getLine()
                ]);
                continue;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'current_bookings' => $currentBookings,
                'history_bookings' => $historyBookings,
            ]
        ]);
    }
}
