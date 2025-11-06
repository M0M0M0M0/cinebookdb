<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DayModifier extends Model
{
    use HasFactory;

    protected $table = 'day_modifiers';
    protected $primaryKey = 'day_modifier_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'day_modifier_id',
        'day_type',
        'modifier_type',
        'modifier_amount',
        'operation',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
