<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // Get all users
    public function index()
    {
        return response()->json(User::all());
    }

    // Update user info (Admin edit)
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $rules = [
            'full_name'     => 'nullable|string|max:100',
            'email'         => 'nullable|email|max:100|unique:web_users,email,' . $id . ',web_user_id',
            'phone_number'  => 'nullable|string|max:20',
            'address'       => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'password'      => 'nullable|string|min:8',
        ];

        $validated = Validator::make($request->all(), $rules);

        if ($validated->fails()) {
            return response()->json(['errors' => $validated->errors()], 422);
        }

        $data = $validated->validated();

        // Hash password if provided
        if (!empty($data['password'])) {
            $data['password_hash'] = Hash::make($data['password']);
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ]);
    }

    // Delete user
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}


