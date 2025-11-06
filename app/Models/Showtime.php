<?php

// app/Models/Showtime.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Showtime extends Model
{
    use HasFactory;

    protected $table = 'showtimes';
    protected $primaryKey = 'showtime_id';

    protected $fillable = [
        'movie_id',
        'room_id',
        'start_time',
        'end_time',
        'base_price',
        'status'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'base_price' => 'decimal:2'
    ];

    // Relationships
    public function movie()
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'movie_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    // Accessor to get theater through room
    public function theater()
    {
        return $this->hasOneThrough(
            Theater::class,
            Room::class,
            'room_id', // Foreign key on rooms table
            'theater_id', // Foreign key on theaters table
            'room_id', // Local key on showtimes table
            'theater_id' // Local key on rooms table
        );
    }

    // Scopes for filtering
    public function scopeByMovie($query, $movieId)
    {
        if ($movieId) {
            return $query->where('movie_id', $movieId);
        }
        return $query;
    }

    public function scopeByTheater($query, $theaterId)
    {
        if ($theaterId) {
            return $query->whereHas('room', function ($q) use ($theaterId) {
                $q->where('theater_id', $theaterId);
            });
        }
        return $query;
    }

    public function scopeByRoom($query, $roomId)
    {
        if ($roomId) {
            return $query->where('room_id', $roomId);
        }
        return $query;
    }

    public function scopeByDate($query, $date)
    {
        if ($date) {
            return $query->whereDate('start_time', $date);
        }
        return $query;
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'Available');
    }

    // Helper method to format for frontend
    public function toArray()
    {
        $array = parent::toArray();

        // Add formatted fields for frontend
        if ($this->start_time) {
            $array['show_date'] = $this->start_time->format('Y-m-d');
            $array['show_time'] = $this->start_time->format('H:i:s');
        }

        return $array;
    }
}
