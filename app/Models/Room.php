<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $primaryKey = 'room_id'; // Khai báo khóa chính nếu không phải 'id'

    protected $fillable = [
        'room_name',
        'room_type',
        'theater_id',
    ];

    // Nếu muốn liên kết với Theater
    public function theater()
    {
        return $this->belongsTo(Theater::class, 'theater_id', 'theater_id');
    }
}
