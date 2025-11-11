<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SeatType;
use Illuminate\Http\Request;

class SeatTypeController extends Controller
{
    // 1. Lấy danh sách (GET /api/seat-types)
    public function index()
    {
        $seatTypes = SeatType::all();
        return response()->json([
            'success' => true,
            'data' => $seatTypes
        ]);
    }

    // 2. Tạo mới (POST /api/seat-types)
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Khóa chính (Ví dụ: 'VIP', 'NORMAL') phải là duy nhất
            'seat_type_id' => 'required|string|max:10|unique:seat_types,seat_type_id',
            'seat_type_name' => 'required|string|max:50',
            'seat_type_price' => 'required|numeric|min:0',
        ]);

        $seatType = SeatType::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Seat type created successfully.',
            'data' => $seatType
        ], 201);
    }

    // 3. Lấy chi tiết (GET /api/seat-types/{id})
    public function show(SeatType $seatType)
    {
        // Eloquent sẽ tự động tìm bản ghi dựa trên seat_type_id
        return response()->json([
            'success' => true,
            'data' => $seatType
        ]);
    }

    // 4. Cập nhật (PUT/PATCH /api/seat-types/{id})
    public function update(Request $request, SeatType $seatType)
    {
        $validated = $request->validate([
            // seat_type_id không thay đổi, chỉ kiểm tra name và price
            'seat_type_name' => 'sometimes|string|max:50',
            'seat_type_price' => 'sometimes|numeric|min:0',
        ]);

        $seatType->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Seat type updated successfully.',
            'data' => $seatType
        ]);
    }

    // 5. Xóa (DELETE /api/seat-types/{id})
    public function destroy(SeatType $seatType)
    {
        $seatType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Seat type deleted successfully.'
        ], 204);
    }
}
