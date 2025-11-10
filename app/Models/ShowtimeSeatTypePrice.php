<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShowtimeSeatTypePrice extends Model
{
    use HasFactory;

    protected $table = 'showtime_seat_type_prices';

    // Composite primary key - Laravel không hỗ trợ tốt, nên ta xử lý manual
    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'showtime_id',
        'seat_type_id',
        'custom_seat_price',
        'is_active',
    ];

    protected $casts = [
        'custom_seat_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function showtime()
    {
        return $this->belongsTo(Showtime::class, 'showtime_id', 'showtime_id');
    }

    public function seatType()
    {
        return $this->belongsTo(SeatType::class, 'seat_type_id', 'seat_type_id');
    }

    /**
     * Override để Laravel không tự động tìm record bằng ID
     */
    protected function setKeysForSaveQuery($query)
    {
        $query->where('showtime_id', $this->getAttribute('showtime_id'))
              ->where('seat_type_id', $this->getAttribute('seat_type_id'));

        return $query;
    }
}
