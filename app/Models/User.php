<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $table = 'web_users';
    protected $primaryKey = 'web_user_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'web_user_id',
        'full_name',
        'date_of_birth',
        'address',
        'phone_number',
        'email',
        'password_hash',
        'role_id',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected $casts = [
    'date_of_birth' => 'date:Y-m-d',
    ];


    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'web_user_id', 'web_user_id');
    }
}
