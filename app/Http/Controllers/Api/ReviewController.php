<?php

// app/Http/Controllers/Api/ReviewController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\Review;
use App\Models\Staff; // ✅ Thêm import Staff
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

        // ✅ KIỂM TRA: Admin (Staff) HOẶC Owner
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
