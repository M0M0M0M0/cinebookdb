<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';
    protected $primaryKey = 'notification_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'notification_id',
        'web_user_id',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'web_user_id', 'web_user_id');
    }
}
