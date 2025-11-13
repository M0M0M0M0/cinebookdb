<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cac extends Model
{
    use HasFactory;

    protected $primaryKey = 'cac_id'; // nếu bạn dùng cac_id làm PK
    public $incrementing = true;      // auto-increment id
    protected $keyType = 'int';

    protected $fillable = [
        'tmdb_id',
        'adult',
        'gender',
        'known_for_department',
        'name',
        'original_name',
        'popularity',
        'profile_path',
        'character',
        'credit_id',
        'cast_order',
        'department',
        'job'
    ];

    public function movies()
    {
        return $this->belongsToMany(Movie::class, 'cac_movie', 'cac_id', 'movie_id')
                    ->withPivot(['role_type', 'credit_id', 'cast_order', 'character', 'department', 'job'])
                    ->withTimestamps();
    }
}
