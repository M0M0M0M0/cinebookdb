<?php
// app/Http/Controllers/Api/ReviewController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    /**
     * Lấy tất cả đánh giá của một bộ phim.
     */
    public function index(Movie $movie)
    {
        // Tùy chỉnh: Tải kèm user với 'web_user_id' và 'full_name'
        $reviews = $movie->reviews()
                         ->with('user:web_user_id,full_name') 
                         ->latest()
                         ->paginate(10); 

        return response()->json($reviews);
    }

    /**
     * Lưu một đánh giá mới (hoặc cập nhật nếu đã tồn tại).
     */
    public function store(Request $request, Movie $movie)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();

        // Tùy chỉnh: Sử dụng 'web_user_id' và khóa chính của user
        $review = $movie->reviews()->updateOrCreate(
            [
                // Điều kiện tìm kiếm: 
                // 'movie_id' đã được ngầm hiểu từ $movie->reviews()
                'web_user_id' => $user->web_user_id, // Lấy khóa chính (string)
            ],
            [
                // Dữ liệu để cập nhật/tạo mới
                'rating' => $validated['rating'], 
                'comment' => $validated['comment'] ?? null,
            ]
        );
        
        // Tải lại thông tin user cho review vừa tạo/cập nhật
        $review->load('user:web_user_id,full_name');

        return response()->json([
            'message' => 'Đánh giá của bạn đã được lưu.',
            'review' => $review
        ], 201);
    }

    /**
     * Xóa một đánh giá.
     */
    public function destroy(Review $review)
    {
        // Tùy chỉnh: 
        // Auth::id() sẽ trả về 'web_user_id' (vì đã định nghĩa trong Model)
        // và so sánh nó với 'web_user_id' của review.
        if (Auth::id() !== $review->web_user_id) {
            return response()->json(['message' => 'Không được phép.'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Đã xóa đánh giá.'], 200);
    }
}