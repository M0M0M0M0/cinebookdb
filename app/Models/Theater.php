<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Theater extends Model
{
    use HasFactory;

    protected $primaryKey = 'theater_id';
    protected $fillable = [
        'theater_name',
        'theater_address',
        'theater_city',
        'theater_capacity',
    ];
}
