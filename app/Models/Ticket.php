<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $table = 'tickets';
    protected $primaryKey = 'ticket_id';

    protected $fillable = [
        'booking_id',
        'seat_id',
        'base_price_snapshot',
        'seat_type_id_snapshot',
        'seat_type_price_snapshot',
        'day_modifier_id_snapshot',
        'day_modifier_snapshot',
        'time_slot_modifier_id_snapshot',
        'time_slot_modifier_snapshot',
        'final_ticket_price',
    ];

    protected $casts = [
        'base_price_snapshot' => 'decimal:2',
        'seat_type_price_snapshot' => 'decimal:2',
        'day_modifier_snapshot' => 'decimal:2',
        'time_slot_modifier_snapshot' => 'decimal:2',
        'final_ticket_price' => 'decimal:2',
    ];

    // Relationships
    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class, 'seat_id', 'seat_id');
    }
}
