<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $table = 'bookings';
    protected $primaryKey = 'booking_id';

    protected $fillable = [
        'web_user_id',
        'showtime_id',
        'status',
        'expires_at',
        'seats_snapshot',
        'foods_snapshot',
    ];

    protected $dates = ['expires_at'];

    public function showtime()
    {
        return $this->belongsTo(Showtime::class, 'showtime_id', 'showtime_id');
    }

    // ✅ THÊM: Relationship với user
    public function user()
    {
        return $this->belongsTo(WebUser::class, 'web_user_id', 'web_user_id');
    }
}
