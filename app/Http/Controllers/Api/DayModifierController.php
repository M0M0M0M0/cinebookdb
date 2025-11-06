<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DayModifier;
use Illuminate\Http\Request;

class DayModifierController extends Controller
{
    // Lấy danh sách (GET /api/day-modifiers)
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => DayModifier::all()
        ]);
    }

    // Tạo mới (POST /api/day-modifiers)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'day_modifier_id' => 'required|string|max:10|unique:day_modifiers,day_modifier_id',
            'day_type' => 'required|string|max:20',
            'modifier_type' => 'required|string|max:15',
            'modifier_amount' => 'required|numeric|min:0',
            'operation' => 'required|string|max:20',
            'is_active' => 'required|boolean',
        ]);

        $modifier = DayModifier::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Day modifier created successfully.',
            'data' => $modifier
        ], 201);
    }

    // Lấy chi tiết (GET /api/day-modifiers/{id})
    public function show(DayModifier $dayModifier)
    {
        return response()->json([
            'success' => true,
            'data' => $dayModifier
        ]);
    }

    // Cập nhật (PUT/PATCH /api/day-modifiers/{id})
    public function update(Request $request, DayModifier $dayModifier)
    {
        $validated = $request->validate([
            // 'sometimes' chỉ validate các trường được gửi lên
            'day_modifier_id' => 'sometimes|string|max:10',
            'day_type' => 'sometimes|string|max:20',
            'modifier_type' => 'sometimes|string|max:15',
            'modifier_amount' => 'sometimes|numeric|min:0',
            'operation' => 'sometimes|string|max:20',
            'is_active' => 'sometimes|boolean',
        ]);

        $dayModifier->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Day modifier updated successfully.',
            'data' => $dayModifier
        ]);
    }

    // Xóa (DELETE /api/day-modifiers/{id})
    public function destroy(DayModifier $dayModifier)
    {
        $dayModifier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Day modifier deleted successfully.'
        ], 204);
    }
}