<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\Ticket;
use App\Models\Showtime;
use App\Models\Seat;
use App\Models\User;
use App\Models\DayModifier;
use App\Models\TimeSlotModifier;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    private $userIds;
    private $showtimeIds;
    private $foods;
    private $bookedSeatsCache = []; // Cache ghế đã book cho mỗi showtime

    private $seatTypeMap = [
        'STD' => 0,
        'GLD' => 6,
        'PLT' => 10,
        'BOX' => 5
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🎬 Bắt đầu tạo 1000 fake bookings...');
        $this->command->info('📅 Thời gian: 15-17/11/2025');
        $this->command->newLine();

        // Load dữ liệu cần thiết
        $this->loadData();

        // Phân bổ status
        $statusDistribution = [
            'completed' => 850,
            'cancelled' => 100,
            'pending' => 50
        ];

        $totalCreated = 0;
        $totalTickets = 0;
        $chunkSize = 200;

        foreach ($statusDistribution as $status => $count) {
            $this->command->info("📊 Tạo {$count} bookings với status: {$status}");

            $chunks = ceil($count / $chunkSize);

            for ($chunk = 0; $chunk < $chunks; $chunk++) {
                $remaining = min($chunkSize, $count - ($chunk * $chunkSize));

                for ($i = 0; $i < $remaining; $i++) {
                    try {
                        $result = $this->createBooking($status);
                        $totalCreated++;
                        $totalTickets += $result['tickets'];

                        // Progress log mỗi 50 bookings
                        if ($totalCreated % 50 == 0) {
                            $this->command->info("   ✅ Đã tạo {$totalCreated}/1000 bookings ({$totalTickets} tickets)");
                        }

                    } catch (\Exception $e) {
                        $this->command->error("   ❌ Lỗi tại booking #{$totalCreated}: " . $e->getMessage());
                        continue;
                    }
                }

                $chunkNumber = $chunk + 1;
                $this->command->info("   💾 Chunk {$chunkNumber}/{$chunks} hoàn thành");
            }

            $this->command->newLine();
        }

        $this->command->info("🎉 HOÀN THÀNH!");
        $this->command->info("📈 Tổng: {$totalCreated} bookings, {$totalTickets} tickets");
    }

    /**
     * Load dữ liệu cần thiết
     */
    private function loadData()
    {
        $this->command->info('📥 Đang load dữ liệu...');

        // Load user IDs
        $this->userIds = User::pluck('web_user_id')->toArray();
        $this->command->info("   ✓ Users: " . count($this->userIds));

        // Load showtime IDs (15-17/11/2025)
        $this->showtimeIds = DB::table('showtimes')
            ->whereBetween('showtime_id', [1, 9402])
            ->pluck('showtime_id')
            ->toArray();
        $this->command->info("   ✓ Showtimes: " . count($this->showtimeIds));

        // Define foods
        $this->foods = [
            ['food_id' => 1, 'food_name' => 'Popcorn', 'price' => 2.00],
            ['food_id' => 2, 'food_name' => 'Soda', 'price' => 1.20],
            ['food_id' => 3, 'food_name' => 'Hotdog', 'price' => 1.60],
            ['food_id' => 4, 'food_name' => 'Combo Popcorn + Soda', 'price' => 2.80],
        ];
        $this->command->info("   ✓ Foods: " . count($this->foods));

        $this->command->newLine();
    }

    /**
     * Tạo một booking
     */
    private function createBooking($status)
    {
        // Random user
        $userId = $this->userIds[array_rand($this->userIds)];

        // Random showtime
        $showtimeId = $this->showtimeIds[array_rand($this->showtimeIds)];
        $showtime = Showtime::find($showtimeId);

        if (!$showtime) {
            throw new \Exception("Showtime {$showtimeId} không tồn tại");
        }

        // Chọn ghế
        $numSeats = rand(1, 4);
        $seats = $this->selectSeats($showtime, $numSeats);

        if (empty($seats)) {
            throw new \Exception("Không thể chọn ghế cho showtime {$showtimeId}");
        }

        // Tạo seat codes
        $seatCodes = array_map(function ($seat) {
            return $seat['row'] . $seat['number'];
        }, $seats);

        // Tạo foods (80% có foods)
        $foodsSnapshot = null;
        if (rand(1, 100) <= 80) {
            $foodsSnapshot = $this->generateFoods($numSeats);
        }

        // Tạo timestamps
        $createdAt = $this->generateCreatedAt($status, $showtime);
        $expiresAt = null;

        if ($status === 'pending') {
            $expiresAt = now()->addMinutes(10);
        }

        // Tạo booking
        $booking = Booking::create([
            'web_user_id' => $userId,
            'showtime_id' => $showtimeId,
            'status' => $status,
            'seats_snapshot' => json_encode($seatCodes),
            'foods_snapshot' => $foodsSnapshot ? json_encode($foodsSnapshot) : null,
            'expires_at' => $expiresAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $ticketCount = 0;

        // Tạo tickets cho completed bookings
        if ($status === 'completed') {
            $ticketCount = $this->createTickets($booking, $showtime, $seats);
        }

        return [
            'booking_id' => $booking->booking_id,
            'tickets' => $ticketCount
        ];
    }

    /**
     * Chọn ghế thông minh
     */
    private function selectSeats($showtime, $numSeats)
    {
        $roomId = $showtime->room_id;
        $showtimeId = $showtime->showtime_id;

        // Load booked seats for this showtime
        if (!isset($this->bookedSeatsCache[$showtimeId])) {
            $booked = DB::table('bookings')
                ->where('showtime_id', $showtimeId)
                ->whereIn('status', ['completed', 'pending'])
                ->pluck('seats_snapshot')
                ->map(function ($json) {
                    return json_decode($json, true) ?? [];
                })
                ->flatten()
                ->toArray();

            $this->bookedSeatsCache[$showtimeId] = $booked;
        }

        $bookedSeats = $this->bookedSeatsCache[$showtimeId];

        // Define seat layout
        $layout = $this->getSeatLayout();

        // Nếu chọn 1 ghế → random bất kỳ
        if ($numSeats === 1) {
            return $this->selectRandomSeat($layout, $bookedSeats);
        }

        // Nếu >= 2 ghế → chọn cùng hàng, liền kề, cùng type
        $selectedSeats = $this->selectAdjacentSeats($layout, $bookedSeats, $numSeats);

        // Update cache
        foreach ($selectedSeats as $seat) {
            $this->bookedSeatsCache[$showtimeId][] = $seat['row'] . $seat['number'];
        }

        return $selectedSeats;
    }

    /**
     * Lấy seat layout
     */
    private function getSeatLayout()
    {
        $layout = [
            'A' => array_fill(1, 16, 'STD'),
            'B' => array_fill(1, 16, 'GLD'),
            'C' => array_fill(1, 16, 'GLD'),
            'D' => array_fill(1, 16, 'GLD'),
            'E' => array_fill(1, 16, 'GLD'),
            'F' => array_fill(1, 16, 'GLD'),
            'G' => array_fill(1, 16, 'BOX'),
        ];

        // B: first 2 and last 2 are STD
        $layout['B'][1] = 'STD';
        $layout['B'][2] = 'STD';
        $layout['B'][15] = 'STD';
        $layout['B'][16] = 'STD';

        // C, D, E: seats 3-14 are PLT
        foreach (['C', 'D', 'E'] as $row) {
            for ($i = 3; $i <= 14; $i++) {
                $layout[$row][$i] = 'PLT';
            }
        }

        return $layout;
    }

    /**
     * Chọn 1 ghế random
     */
    private function selectRandomSeat($layout, $bookedSeats)
    {
        $attempts = 0;
        $maxAttempts = 100;

        while ($attempts < $maxAttempts) {
            $row = array_rand($layout);
            $number = rand(1, 16);
            $seatCode = $row . $number;

            if (!in_array($seatCode, $bookedSeats)) {
                return [[
                    'row' => $row,
                    'number' => $number,
                    'type' => $layout[$row][$number]
                ]];
            }

            $attempts++;
        }

        throw new \Exception("Không tìm được ghế trống");
    }

    /**
     * Chọn ghế liền kề
     */
    private function selectAdjacentSeats($layout, $bookedSeats, $numSeats)
    {
        // Nếu chọn hàng G (couple) → chỉ cho phép 2 ghế
        $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        shuffle($rows);

        foreach ($rows as $row) {
            // Nếu hàng G và yêu cầu > 2 ghế → skip
            if ($row === 'G' && $numSeats > 2) {
                continue;
            }

            // Nếu hàng G → chọn theo cặp (1-2, 3-4, 5-6...)
            if ($row === 'G') {
                $pairs = [[1,2], [3,4], [5,6], [7,8], [9,10], [11,12], [13,14], [15,16]];
                shuffle($pairs);

                foreach ($pairs as $pair) {
                    $seat1 = $row . $pair[0];
                    $seat2 = $row . $pair[1];

                    if (!in_array($seat1, $bookedSeats) && !in_array($seat2, $bookedSeats)) {
                        return [
                            ['row' => $row, 'number' => $pair[0], 'type' => 'BOX'],
                            ['row' => $row, 'number' => $pair[1], 'type' => 'BOX'],
                        ];
                    }
                }
            } else {
                // Hàng thường → tìm dãy liền kề
                for ($start = 1; $start <= 16 - $numSeats + 1; $start++) {
                    $canUse = true;
                    $selectedSeats = [];
                    $firstType = $layout[$row][$start];

                    for ($i = 0; $i < $numSeats; $i++) {
                        $num = $start + $i;
                        $seatCode = $row . $num;
                        $seatType = $layout[$row][$num];

                        // Kiểm tra ghế trống và cùng type
                        if (in_array($seatCode, $bookedSeats) || $seatType !== $firstType) {
                            $canUse = false;
                            break;
                        }

                        $selectedSeats[] = [
                            'row' => $row,
                            'number' => $num,
                            'type' => $seatType
                        ];
                    }

                    if ($canUse) {
                        return $selectedSeats;
                    }
                }
            }
        }

        throw new \Exception("Không tìm được {$numSeats} ghế liền kề");
    }

    /**
     * Generate foods
     */
    private function generateFoods($numSeats)
    {
        $selectedFoods = [];
        $totalQuantity = 0;
        $numFoodTypes = rand(1, min(3, count($this->foods)));

        $availableFoods = $this->foods;
        shuffle($availableFoods);

        for ($i = 0; $i < $numFoodTypes; $i++) {
            if ($totalQuantity >= $numSeats) {
                break;
            }

            $food = $availableFoods[$i];
            $maxQty = $numSeats - $totalQuantity;
            $quantity = rand(1, $maxQty);

            $selectedFoods[] = [
                'food_id' => $food['food_id'],
                'food_name' => $food['food_name'],
                'quantity' => $quantity,
                'price' => $food['price']
            ];

            $totalQuantity += $quantity;
        }

        return $selectedFoods;
    }

    /**
     * Generate created_at timestamp
     */
    private function generateCreatedAt($status, $showtime)
    {
        $showtimeDate = Carbon::parse($showtime->start_time);

        if ($status === 'completed') {
            // 1-7 ngày trước showtime
            return $showtimeDate->copy()->subDays(rand(1, 7))->subHours(rand(0, 23));
        }

        if ($status === 'pending') {
            // Trong vòng 1 giờ gần đây
            return now()->subMinutes(rand(1, 60));
        }

        if ($status === 'cancelled') {
            // Random trong quá khứ, đã expired
            return now()->subDays(rand(1, 10))->subHours(rand(0, 23));
        }

        return now();
    }

    /**
     * Tạo tickets cho booking
     */
    private function createTickets($booking, $showtime, $seats)
    {
        $basePrice = 10; // Base price cố định

        // Lấy modifiers
        $dayModifier = $this->getDayModifierForShowtime($showtime);
        $timeModifier = $this->getTimeSlotModifierForShowtime($showtime);

        $ticketCount = 0;

        foreach ($seats as $seatData) {
            // Lấy seat từ DB
            $seat = Seat::where('room_id', $showtime->room_id)
                ->where('seat_row', $seatData['row'])
                ->where('seat_number', $seatData['number'])
                ->first();

            if (!$seat) {
                continue;
            }

            // Lấy giá seat type
            $seatTypePrice = $this->seatTypeMap[$seatData['type']] ?? 0;

            // Check custom price (nếu có)
            $customPrice = DB::table('showtime_seat_type_prices')
                ->where('showtime_id', $showtime->showtime_id)
                ->where('seat_type_id', $seat->seat_type_id)
                ->where('is_active', 1)
                ->value('custom_seat_price');

            if ($customPrice !== null) {
                $seatTypePrice = $customPrice;
            }

            // Tính giá
            $priceBeforeModifiers = $basePrice + $seatTypePrice;

            // Apply Day Modifier
            $priceAfterDay = $priceBeforeModifiers + $dayModifier['amount'];
            $dayModifierValue = $dayModifier['amount'];

            // Apply Time Slot Modifier
            $finalPrice = $priceAfterDay + $timeModifier['amount'];
            $timeModifierValue = $timeModifier['amount'];

            $finalPrice = round($finalPrice, 2);

            // Tạo ticket
            Ticket::create([
                'booking_id' => $booking->booking_id,
                'seat_id' => $seat->seat_id,
                'base_price_snapshot' => $basePrice,
                'seat_type_id_snapshot' => $seat->seat_type_id,
                'seat_type_price_snapshot' => $seatTypePrice,
                'day_modifier_id_snapshot' => $dayModifier['id'],
                'day_modifier_snapshot' => $dayModifierValue,
                'time_slot_modifier_id_snapshot' => $timeModifier['id'],
                'time_slot_modifier_snapshot' => $timeModifierValue,
                'final_ticket_price' => $finalPrice
            ]);

            $ticketCount++;
        }

        return $ticketCount;
    }

    /**
     * Lấy Day Modifier
     */
    private function getDayModifierForShowtime($showtime)
    {
        $dayOfWeek = Carbon::parse($showtime->start_time)->dayOfWeek;
        $dayType = in_array($dayOfWeek, [0, 6]) ? 'Weekend' : 'Weekday';

        $modifier = DayModifier::where('day_type', $dayType)
            ->where('is_active', 1)
            ->first();

        if (!$modifier) {
            return ['id' => null, 'amount' => 0];
        }

        // Fixed amount
        $amount = (float)$modifier->modifier_amount;

        if ($modifier->operation === 'decrease') {
            $amount = -$amount;
        }

        return [
            'id' => $modifier->day_modifier_id,
            'amount' => $amount
        ];
    }

    /**
     * Lấy Time Slot Modifier
     */
    private function getTimeSlotModifierForShowtime($showtime)
    {
        $startTime = Carbon::parse($showtime->start_time)->format('H:i:s');

        $modifier = TimeSlotModifier::where('ts_start_time', '<=', $startTime)
            ->where('ts_end_time', '>', $startTime)
            ->where('is_active', 1)
            ->first();

        if (!$modifier) {
            return ['id' => null, 'amount' => 0];
        }

        // Fixed amount
        $amount = (float)$modifier->ts_amount;

        if ($modifier->operation === 'decrease') {
            $amount = -$amount;
        }

        return [
            'id' => $modifier->time_slot_modifier_id,
            'amount' => $amount
        ];
    }
}
