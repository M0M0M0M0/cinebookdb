<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $user->update($request->only('full_name', 'address', 'phone_number')); // sửa các trường cần update
        return response()->json(['user' => $user]);
    }

}
