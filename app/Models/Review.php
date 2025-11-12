<?php
// app/Models/Review.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    /**
     * Tên bảng mà model này quản lý.
     * (Laravel tự động đoán là 'reviews', 
     * nhưng thêm vào cho rõ ràng)
     */
    protected $table = 'reviews';

    /**
     * Khóa chính của bảng 'reviews' là 'id' (mặc định)
     * protected $primaryKey = 'id';
     */

    /**
     * Các trường được phép gán hàng loạt (mass assignable).
     * Chúng ta sử dụng 'web_user_id' và 'movie_id'
     * khớp với migration.
     */
    protected $fillable = [
        'web_user_id',
        'movie_id',
        'rating',
        'comment',
    ];

    /**
     * Lấy thông tin user đã viết review này.
     * (Quan hệ ngược: Một Review thuộc về một User)
     */
    public function user()
    {
        // 'App\Models\User' là class User.
        // 'web_user_id' là khóa ngoại (foreign key) trong bảng 'reviews'.
        // 'web_user_id' là khóa sở hữu (owner key) trong bảng 'web_users'.
        return $this->belongsTo(User::class, 'web_user_id', 'web_user_id');
    }

    /**
     * Lấy thông tin phim được review.
     * (Quan hệ ngược: Một Review thuộc về một Movie)
     */
    public function movie()
    {
        // 'App\Models\Movie' là class Movie.
        // 'movie_id' là khóa ngoại (foreign key) trong bảng 'reviews'.
        // 'movie_id' là khóa sở hữu (owner key) trong bảng 'movies'.
        return $this->belongsTo(Movie::class, 'movie_id', 'movie_id');
    }
}