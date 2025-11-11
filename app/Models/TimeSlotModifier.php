<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeSlotModifier extends Model
{
    use HasFactory;

    protected $table = 'time_slot_modifiers';
    protected $primaryKey = 'time_slot_modifier_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'time_slot_modifier_id',
        'time_slot_name',
        'ts_start_time',
        'ts_end_time',
        'modifier_type',
        'ts_amount',
        'operation',
        'is_active',
    ];

    // Khai báo các trường thời gian
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
