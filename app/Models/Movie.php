<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    protected $primaryKey = 'movie_id';
    public $incrementing = false;

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
    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'movie_genre', 'movie_id', 'genre_id');
    }
}
