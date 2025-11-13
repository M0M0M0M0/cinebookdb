<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SendShowtimeRemindersJob extends Job
{
    public function handle()
    {
        $now = Carbon::now();
        $target = $now->copy()->addHours(2);

        $bookings = Booking::with('showtime.movie')
            ->where('status', 'confirmed')
            ->whereHas('showtime', function ($q) use ($now, $target) {
                $q->whereBetween('start_time', [$now, $target]);
            })
            ->get();

        foreach ($bookings as $booking) {
            Notification::create([
                'notification_id' => Str::uuid(),
                'web_user_id' => $booking->web_user_id,
                'type' => 'reminder',
                'message' => '⏰ Phim ' . $booking->showtime->movie->title . ' sẽ bắt đầu sau 2 tiếng!',
            ]);
        }
    }
}

