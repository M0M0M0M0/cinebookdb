<?php

// app/Models/Movie.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $table = 'movies';
    protected $primaryKey = 'movie_id';
    public $incrementing = false; // vì movie_id là integer không auto increment

    protected $fillable = [
        'movie_id',
        'original_language',
        'original_title',
        'overview',
        'poster_path',
        'backdrop_path',
        'release_date',
        'title',
        'vote_average',
        'duration',
        'trailer_link'
    ];

    protected $casts = [
        'release_date' => 'date',
        'vote_average' => 'decimal:1'
    ];

    public function showtimes()
    {
        return $this->hasMany(Showtime::class, 'movie_id', 'movie_id');
    }
    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'movie_genre', 'movie_id', 'genre_id');
    }


    // Accessor for poster URL
    public function getPosterAttribute()
    {
        if ($this->poster_path) {
            return 'https://image.tmdb.org/t/p/original' . $this->poster_path;
        }
        return null;
    }
    public function reviews()
    {
        return $this->hasMany(Review::class, 'movie_id', 'movie_id');
    }

    /**
     * (Khuyến khích) Accessor để lấy điểm rating trung bình
     */
    public function getAverageRatingAttribute()
    {
        // Lấy trung bình cộng của cột 'rating' và làm tròn 1 chữ số
        return round($this->reviews()->avg('rating'), 1);
    }
    public function cacs()
    {
        return $this->belongsToMany(\App\Models\Cac::class, 'cac_movie', 'movie_id', 'cac_id')
                    ->withPivot(['role_type', 'credit_id', 'cast_order', 'character', 'department', 'job'])
                    ->withTimestamps();
    }
    public function casts()
    {
        return $this->belongsToMany(Cac::class, 'cac_movie', 'movie_id', 'cac_id')
            ->wherePivot('role_type', 'cast')
            ->withPivot(['character', 'cast_order', 'credit_id'])
            ->orderByPivot('cast_order', 'asc')
            ->withTimestamps();
    }

    /**
     * Lấy danh sách crew của phim
     */
    public function crews()
    {
        return $this->belongsToMany(Cac::class, 'cac_movie', 'movie_id', 'cac_id')
            ->wherePivot('role_type', 'crew')
            ->withPivot(['department', 'job', 'credit_id'])
            ->withTimestamps();
    }
}
