<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\Review;
use App\Models\Staff;
use App\Models\WebUser;
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
        $reviews = $movie->reviews()
                         ->with('user:web_user_id,full_name')
                         ->latest()
                         ->paginate(10);

        // ✅ Thêm thông tin user_type vào mỗi review
        $reviews->getCollection()->transform(function ($review) {
            $review->user_type = 'customer'; // Mặc định là customer
            return $review;
        });

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

        // ✅ Xác định user type
        $isStaff = $user instanceof Staff;

        if ($isStaff) {
            // ✅ Admin bình luận - tạo review với staff info
            $review = Review::create([
                'movie_id' => $movie->movie_id,
                'web_user_id' => null, // Staff không có web_user_id
                'staff_id' => $user->staff_id, // ✅ Lưu staff_id
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]);

            // Tạo object user giả để frontend hiển thị
            $review->user = (object)[
                'full_name' => $user->full_name,
                'web_user_id' => null,
            ];
            $review->user_type = 'staff'; // ✅ Đánh dấu là staff

        } else {
            // Customer bình luận
            $review = $movie->reviews()->updateOrCreate(
                [
                    'web_user_id' => $user->web_user_id,
                ],
                [
                    'rating' => $validated['rating'],
                    'comment' => $validated['comment'] ?? null,
                ]
            );

            $review->load('user:web_user_id,full_name');
            $review->user_type = 'customer';
        }

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
        $user = Auth::user();

        $isAdmin = $user instanceof Staff;
        $isOwner = Auth::id() === $review->web_user_id;

        if (!$isAdmin && !$isOwner) {
            return response()->json(['message' => 'Không được phép.'], 403);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa đánh giá.'
        ], 200);
    }
}
