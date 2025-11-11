<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserProfileController extends Controller
{
    /**
     * ✅ Get current user profile
     */
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user()
        ]);
    }

    /**
     * ✅ Update user profile (excluding email and password)
     */
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
            'success' => true,
            'message' => 'Profile updated successfully!',
            'user'    => $user
        ]);
    }

    /**
     * ✅ Change password
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8',
            'confirm_password' => 'required|string|same:new_password',
        ]);

        $user = $request->user();

        // Check current password
        if (!Hash::check($validated['current_password'], $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'The current password is incorrect.'
            ], 400);
        }

        // Update new password
        $user->update([
            'password_hash' => Hash::make($validated['new_password'])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully!'
        ]);
    }
}
