<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimeSlotModifier;
use Illuminate\Http\Request;

class TimeSlotModifierController extends Controller
{
    // Lấy danh sách (GET /api/time-slot-modifiers)
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => TimeSlotModifier::all()
        ]);
    }

    // Tạo mới (POST /api/time-slot-modifiers)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'time_slot_modifier_id' => 'required|string|max:10|unique:time_slot_modifiers,time_slot_modifier_id',
            'time_slot_name' => 'required|string|max:255',
            'ts_start_time' => 'required|date_format:H:i:s', // Định dạng giờ:phút:giây
            'ts_end_time' => 'required|date_format:H:i:s|after:ts_start_time',
            'modifier_type' => 'required|string|max:15',
            'ts_amount' => 'required|numeric|min:0',
            'operation' => 'required|string|max:20',
            'is_active' => 'required|boolean',
        ]);

        $modifier = TimeSlotModifier::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Time slot modifier created successfully.',
            'data' => $modifier
        ], 201);
    }

    // Lấy chi tiết (GET /api/time-slot-modifiers/{id})
    public function show(TimeSlotModifier $timeSlotModifier)
    {
        return response()->json([
            'success' => true,
            'data' => $timeSlotModifier
        ]);
    }

    // Cập nhật (PUT/PATCH /api/time-slot-modifiers/{id})
    public function update(Request $request, TimeSlotModifier $timeSlotModifier)
    {
        $validated = $request->validate([
            // 'sometimes' chỉ validate các trường được gửi lên
            'time_slot_modifier_id' => 'sometimes|string|max:10',
            'time_slot_name' => 'sometimes|string|max:255',
            'ts_start_time' => 'sometimes|date_format:H:i:s',
            'ts_end_time' => 'sometimes|date_format:H:i:s|after:ts_start_time',
            'modifier_type' => 'sometimes|string|max:15',
            'ts_amount' => 'sometimes|numeric|min:0',
            'operation' => 'sometimes|string|max:20',
            'is_active' => 'sometimes|boolean',
        ]);

        $timeSlotModifier->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Time slot modifier updated successfully.',
            'data' => $timeSlotModifier
        ]);
    }

    // Xóa (DELETE /api/time-slot-modifiers/{id})
    public function destroy(TimeSlotModifier $timeSlotModifier)
    {
        $timeSlotModifier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Time slot modifier deleted successfully.'
        ], 204);
    }
}
