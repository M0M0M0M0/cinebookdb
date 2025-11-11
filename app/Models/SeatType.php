<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeatType extends Model
{
    use HasFactory;

    // Khai báo rõ ràng tên bảng (nếu tên Model không phải là số ít của tên bảng)
    protected $table = 'seat_types';

    // Khai báo khóa chính
    protected $primaryKey = 'seat_type_id';

    // Khai báo kiểu dữ liệu của khóa chính là string
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'seat_type_id',
        'seat_type_name',
        'seat_type_price',
    ];
}
