<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // ✅ Get all users
    public function index()
    {
        return response()->json(User::all());
    }

    // ✅ Update user info
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'full_name'     => 'nullable|string|max:100',
            'address'       => 'nullable|string|max:255',
            'phone_number'  => 'nullable|string|max:15',
            'date_of_birth' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->update($validator->validated());

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    // ✅ Delete user
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    // ✅ Toggle active/locked status
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->status = $user->status === 'active' ? 'locked' : 'active';
        $user->save();

        return response()->json([
            'message' => 'User status updated successfully',
            'status' => $user->status
        ]);
    }
}

