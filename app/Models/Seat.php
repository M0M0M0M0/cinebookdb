<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    use HasFactory;

    protected $table = 'seats';
    protected $primaryKey = 'seat_id';

    protected $fillable = [
        'seat_row',
        'seat_number',
        'seat_type_id',
        'room_id',
    ];

    protected $casts = [
        'seat_number' => 'integer',
    ];

    // Relationships
    public function seatType()
    {
        return $this->belongsTo(SeatType::class, 'seat_type_id', 'seat_type_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'seat_id', 'seat_id');
    }

    /**
     * Accessor: Lấy mã ghế dạng "A1", "B5", etc.
     */
    public function getSeatCodeAttribute()
    {
        return $this->seat_row . $this->seat_number;
    }

    /**
     * Scope: Tìm ghế theo code (vd: "A1")
     */
    public function scopeBySeatCode($query, $seatCode)
    {
        $row = substr($seatCode, 0, 1);
        $number = substr($seatCode, 1);
        
        return $query->where('seat_row', $row)
                     ->where('seat_number', $number);
    }
}