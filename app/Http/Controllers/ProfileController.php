<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function profile(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $user->full_name = $request->full_name;
        $user->date_of_birth = $request->dob;
        $user->phone_number = $request->phone;
        $user->address = $request->address;

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully'
        ]);
    }
}
