<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // Lấy thông tin người dùng hiện tại
    public function profile(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }

    // Cập nhật thông tin cơ bản (không bao gồm email, mật khẩu)
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'full_name'     => 'nullable|string|max:100',
            'address'       => 'nullable|string|max:255',
            'phone_number'  => 'nullable|string|max:15',
            'date_of_birth' => 'nullable|date',
        ]);

        $user = $request->user();
        $user->update($validated);

        return response()->json([
            'message' => 'Cập nhật thông tin thành công',
            'user'    => $user
        ]);
    }

    // Đổi mật khẩu
    // Đổi mật khẩu
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password'      => 'required|string',
            'new_password'          => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        // Kiểm tra mật khẩu hiện tại
        if (!Hash::check($validated['current_password'], $user->password_hash)) {
            return response()->json([
                'message' => 'Mật khẩu hiện tại không đúng'
            ], 400);
        }

        // Cập nhật mật khẩu mới
        $user->update([
            'password_hash' => Hash::make($validated['new_password']),
        ]);

        return response()->json([
            'message' => 'Đổi mật khẩu thành công'
        ]);
    }

}
