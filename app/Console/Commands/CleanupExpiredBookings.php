<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupExpiredBookings extends Command
{
    protected $signature = 'bookings:cleanup';
    protected $description = 'Cancels expired pending bookings, releasing the seats.';

    public function handle()
    {
        $expiredBookings = Booking::where('status', 'pending') // Chỉ hủy các booking đang giữ ghế
                                    ->where('expires_at', '<', now())
                                    ->get();

        $count = 0;
        DB::beginTransaction();
        try {
            foreach ($expiredBookings as $booking) {
                $booking->status = 'cancelled'; // Đổi trạng thái sang hủy
                $booking->save();
                $count++;
            }
            DB::commit();
            $this->info("Successfully cancelled {$count} expired bookings.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('An error occurred during cleanup: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
