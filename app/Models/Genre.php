<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    protected $table = 'genres';
    protected $primaryKey = 'genre_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'genre_id',
        'name'
    ];
    public function movies()
    {
        return $this->belongsToMany(Movie::class, 'movie_genre', 'genre_id', 'movie_id');
    }

}
