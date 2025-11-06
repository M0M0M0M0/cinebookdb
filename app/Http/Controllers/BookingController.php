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
    // 1. HELPER FUNCTIONS (Logic Lấy Modifier)

    protected function getDayModifierForShowtime(Showtime $showtime)
    {
        $dayOfWeek = $showtime->start_time->format('l');

        // Tìm modifier theo tên ngày
        $modifier = DayModifier::where('day_name', $dayOfWeek)->first();

        return [
            'id' => $modifier->day_modifier_id ?? null,
            'multiplier' => (float)($modifier->day_modifier_snapshot ?? 1.0),
        ];
    }


    protected function getTimeSlotModifierForShowtime(Showtime $showtime)
    {
        $startTime = $showtime->start_time->format('H:i:s');

        $modifier = TimeSlotModifier::where('start_time', '<=', $startTime)
                                     ->where('end_time', '>', $startTime)
                                     ->first();


        return [
            'id' => $modifier->time_slot_modifier_id ?? null,
            'multiplier' => (float)($modifier->time_slot_modifier_snapshot ?? 1.0),
        ];
    }

    // 2. PUBLIC API METHODS


    public function getSoldSeats($showtime_id)
    {

        $showtime = Showtime::find($showtime_id);
        if (!$showtime) {
            return response()->json(['success' => false, 'message' => 'Showtime not found.'], 404);
        }

        $soldSeats = DB::table('bookings as b')
            ->join('tickets as t', 'b.booking_id', '=', 't.booking_id')
            ->join('seats as s', 't.seat_id', '=', 's.seat_id')
            ->where('b.showtime_id', $showtime_id)
            ->whereIn('b.status', ['completed', 'sold-out'])
            ->select(DB::raw("CONCAT(s.seat_row, s.seat_number) as code"))
            ->get()
            ->map(function ($seat) {
                return ['code' => $seat->code, 'status' => 'sold'];
            })
            ->toArray();
        $seatTypes = DB::table('seat_types')
            ->select('seat_type_id', 'seat_type_name', 'seat_type_price')
            ->get()
            ->keyBy('seat_type_name');

        return response()->json([
    'success' => true,
    'data' => [
        'base_showtime_price' => (float) $showtime->base_price,
        'reserved_seats' => $soldSeats,
        'seat_type_prices' => $seatTypes, // <--- Đã thêm
    ]
]);
    }

    /**
     * Tạo Booking và Tickets mới (sau khi thanh toán thành công).
     */
    public function createBooking(Request $request)
    {
        $request->validate([
            'showtime_id' => 'required|exists:showtimes,showtime_id',
            'seat_codes' => 'required|array|min:1',
            'seat_codes.*' => 'string',
        ]);

        $showtimeId = $request->input('showtime_id');
        $seatCodes = $request->input('seat_codes');
        $webUserId = auth()->id();

        DB::beginTransaction();

        try {
            // Lấy dữ liệu cơ sở
            $showtime = Showtime::find($showtimeId);
            $basePrice = (float)$showtime->base_price;

            // Lấy modifiers áp dụng (Sử dụng các hàm helper mới)
            $dayModifier = $this->getDayModifierForShowtime($showtime);
            $timeModifier = $this->getTimeSlotModifierForShowtime($showtime);

            $dayModifierId = $dayModifier['id'];
            $dayModifierValue = $dayModifier['multiplier'];
            $timeModifierId = $timeModifier['id'];
            $timeModifierValue = $timeModifier['multiplier'];

            // Lấy dữ liệu ghế và phụ phí từ DB
            $seatsData = DB::table('seats as s')
                ->join('seat_types as st', 's.seat_type_id', '=', 'st.seat_type_id')
                ->where('s.room_id', $showtime->room_id)
                ->whereIn(DB::raw("CONCAT(s.seat_row, s.seat_number)"), $seatCodes)
                ->select('s.seat_id', 's.seat_type_id', 'st.seat_type_price')
                ->get();

            // Kiểm tra tính khả dụng của ghế
            $soldSeatsCodes = collect($this->getSoldSeats($showtimeId)->original['data']['reserved_seats'])->pluck('code')->toArray();
            if (count(array_intersect($seatCodes, $soldSeatsCodes)) > 0) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Some selected seats are already sold.'], 400);
            }

            // Tạo Booking
            $booking = Booking::create([
                'web_user_id' => $webUserId,
                'showtime_id' => $showtimeId,
                'booking_date' => now(),
                'status' => 'completed',
            ]);

            $finalTotal = 0;

            // Tạo Tickets
            foreach ($seatsData as $seat) {
                // Tính toán giá: (Giá Cơ Sở + Phụ phí Loại Ghế) * Day Modifier * Time Modifier
                $priceBeforeModifiers = $basePrice + (float)$seat->seat_type_price;
                $priceAfterDayMod = $priceBeforeModifiers * $dayModifierValue;
                $finalPrice = $priceAfterDayMod * $timeModifierValue;

                $finalTotal += $finalPrice;

                Ticket::create([
                    'booking_id' => $booking->booking_id,
                    'seat_id' => $seat->seat_id,

                    // Snapshot Giá Cơ sở và Phụ phí
                    'base_price_snapshot' => $basePrice,
                    'seat_type_id_snapshot' => $seat->seat_type_id,
                    'seat_type_price_snapshot' => $seat->seat_type_price,

                    // Snapshot Day Modifier
                    'day_modifier_id_snapshot' => $dayModifierId,
                    'day_modifier_snapshot' => $dayModifierValue,

                    // Snapshot Time Slot Modifier
                    'time_slot_modifier_id_snapshot' => $timeModifierId,
                    'time_slot_modifier_snapshot' => $timeModifierValue,

                    // Giá Cuối cùng đã tính toán
                    'final_ticket_price' => round($finalPrice, 2),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully.',
                'data' => [
                    'booking_id' => $booking->booking_id,
                    'total_amount' => round($finalTotal, 2),
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred.'], 500);
        }
    }
}
