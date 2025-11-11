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

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }
}
