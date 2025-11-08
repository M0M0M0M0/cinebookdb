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
    protected function getDayModifierForShowtime(Showtime $showtime)
    {
        $dayOfWeek = $showtime->start_time->format('l');
        $modifier = DayModifier::where('day_name', $dayOfWeek)->first();

        return [
            'id' => $modifier->day_modifier_id ?? null,
            'multiplier' => (float)($modifier->day_modifier_snapshot ?? 1.0),
        ];
    }

    protected function getTimeSlotModifierForShowtime(Showtime $showtime)
    {
        $startTime = $showtime->start_time->format('H:i:s');
        $modifier  = TimeSlotModifier::where('start_time', '<=', $startTime)
                                     ->where('end_time', '>', $startTime)
                                     ->first();

        return [
            'id' => $modifier->time_slot_modifier_id ?? null,
            'multiplier' => (float)($modifier->time_slot_modifier_snapshot ?? 1.0),
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
            'booking_id' => 'required|exists:bookings,booking_id'
        ]);

        $booking = Booking::find($request->booking_id);

        if (!$booking || $booking->status !== 'pending') {
            return response()->json(['success' => false,'message' => 'Invalid Booking'], 400);
        }

        if (now()->greaterThan($booking->expires_at)) {
            // hết hạn → xoá booking
            $booking->delete();
            return response()->json(['success' => false,'message' => 'Booking expired'], 410);
        }

        DB::beginTransaction();

        try {
            $showtime = $booking->showtime;
            $basePrice = $showtime->base_price;

            $dayModifier  = $this->getDayModifierForShowtime($showtime);
            $timeModifier = $this->getTimeSlotModifierForShowtime($showtime);

            // get seats by tickets user selected before (client phải gửi seat_codes lại hoặc snapshot)
            $seatCodes = $request->seat_codes;

            $seatsData = DB::table('seats as s')
                ->join('seat_types as st', 's.seat_type_id', '=', 'st.seat_type_id')
                ->where('s.room_id', $showtime->room_id)
                ->whereIn(DB::raw("CONCAT(s.seat_row,s.seat_number)"), $seatCodes)
                ->select('s.seat_id', 's.seat_type_id', 'st.seat_type_price')
                ->get();

            $total = 0;

            foreach ($seatsData as $s) {
                $priceBefore  = $basePrice + $s->seat_type_price;
                $priceAfterDay = $priceBefore * $dayModifier['multiplier'];
                $final         = $priceAfterDay * $timeModifier['multiplier'];
                $total += $final;

                Ticket::create([
                    'booking_id' => $booking->booking_id,
                    'seat_id'    => $s->seat_id,
                    'base_price_snapshot' => $basePrice,
                    'seat_type_id_snapshot' => $s->seat_type_id,
                    'seat_type_price_snapshot' => $s->seat_type_price,
                    'day_modifier_id_snapshot' => $dayModifier['id'],
                    'day_modifier_snapshot' => $dayModifier['multiplier'],
                    'time_slot_modifier_id_snapshot' => $timeModifier['id'],
                    'time_slot_modifier_snapshot' => $timeModifier['multiplier'],
                    'final_ticket_price' => round($final, 2)
                ]);
            }

            // update booking
            $booking->status = 'completed';
            $booking->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment completed',
                'total_amount' => round($total, 2)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false,'message' => 'unexpected error'], 500);
        }
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

        // Lấy tất cả bookings có ghế cho showtime này
        $bookings = DB::table('bookings')
            ->where('showtime_id', $showtime_id)
            ->where(function ($q) {
                $q->where('status', 'completed')
                    ->orWhere(function ($qq) {
                        $qq->where('status', 'pending')
                            ->where('expires_at', '>', now());
                    });
            })
            ->select('seats_snapshot')
            ->get();

        // Parse JSON và lấy seat codes
        $soldSeats = [];
        foreach ($bookings as $booking) {
            if ($booking->seats_snapshot) {
                $seats = json_decode($booking->seats_snapshot, true);
                if (is_array($seats)) {
                    foreach ($seats as $seat) {
                        // Giả sử seats_snapshot có format: [{"code": "A1", ...}, {"code": "A2", ...}]
                        // Hoặc đơn giản là: ["A1", "A2", "A3"]
                        if (is_string($seat)) {
                            $soldSeats[] = ['code' => $seat, 'status' => 'sold'];
                        } elseif (isset($seat['code'])) {
                            $soldSeats[] = ['code' => $seat['code'], 'status' => 'sold'];
                        }
                    }
                }
            }
        }

        // Remove duplicates
        $soldSeats = array_values(array_unique($soldSeats, SORT_REGULAR));

        // Lấy thông tin seat type prices
        $seatTypePrices = DB::table('seat_types')
            ->select('seat_type_name', 'seat_type_price')
            ->get()
            ->keyBy('seat_type_name')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'base_showtime_price' => (float)$showtime->base_price,
                'reserved_seats' => $soldSeats,
                'seat_type_prices' => $seatTypePrices
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
    public function addFoods(Request $request, $booking_id)
    {
        $request->validate(['foods' => 'nullable|array']);

        $booking = Booking::find($booking_id);

        // Kiểm tra booking hợp lệ và còn hạn
        if (!$booking || $booking->status !== 'pending' || now()->greaterThan($booking->expires_at)) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired Booking'], 400);
        }

        // CẬP NHẬT SNAPSHOT ĐỒ ĂN
        $booking->foods_snapshot = json_encode($request->foods ?? []);
        $booking->save();

        return response()->json(['success' => true, 'message' => 'Food snapshot updated']);
    }
}
