<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Food;
use Illuminate\Http\Request;

class FoodController extends Controller
{
    // --- 1. READ: Lấy danh sách tất cả đồ ăn (GET /api/foods) ---
    public function index()
    {
        $foods = Food::all();
        return response()->json([
            'success' => true,
            'data' => $foods
        ]);
    }

    // --- 2. CREATE: Tạo một món ăn mới (POST /api/foods) ---
    public function store(Request $request)
    {
        // Validation (Xác thực dữ liệu đầu vào)
        $validated = $request->validate([
            'food_name' => 'required|string|max:100|unique:foods,food_name',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'status' => 'nullable|string|max:50',
        ]);

        $food = Food::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Food item created successfully.',
            'data' => $food
        ], 201);
    }

    // --- 3. READ (Single): Lấy thông tin chi tiết (GET /api/foods/{id}) ---
    public function show(Food $food)
    {
        return response()->json([
            'success' => true,
            'data' => $food
        ]);
    }

    // --- 4. UPDATE: Cập nhật thông tin (PUT/PATCH /api/foods/{id}) ---
    public function update(Request $request, Food $food)
    {
        // Validation cho UPDATE
        $validated = $request->validate([
            // Bỏ qua bản ghi hiện tại khi kiểm tra uniqueness
            'food_name' => 'sometimes|string|max:100|unique:foods,food_name,' . $food->food_id . ',food_id',
            'description' => 'nullable|string',
            'base_price' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|string|max:50',
        ]);

        $food->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Food item updated successfully.',
            'data' => $food
        ]);
    }

    // --- 5. DELETE: Xóa một món ăn (DELETE /api/foods/{id}) ---
    public function destroy(Food $food)
    {
        $food->delete();

        return response()->json([
            'success' => true,
            'message' => 'Food item deleted successfully.'
        ], 204);
    }
}
