<?php

// app/Models/Theater.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Theater extends Model
{
    use HasFactory;

    protected $table = 'theaters';
    protected $primaryKey = 'theater_id';

    protected $fillable = [
        'theater_name',
        'theater_address',
        'theater_city'
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class, 'theater_id', 'theater_id');
    }

    public function showtimes()
    {
        return $this->hasManyThrough(
            Showtime::class,
            Room::class,
            'theater_id',
            'room_id',
            'theater_id',
            'room_id'
        );
    }
}
