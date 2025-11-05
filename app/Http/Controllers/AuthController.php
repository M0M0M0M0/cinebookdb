<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuthController extends Controller
{
    // Đăng nhập
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password_hash)) {
            return response()->json(['message' => 'Sai email hoặc mật khẩu'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Đăng nhập thành công',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    // Đăng ký tài khoản
    public function register(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:100',
            'date_of_birth' => 'required|date',
            'address' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'web_user_id'   => (string) Str::uuid(),
            'full_name'     => $request->full_name,
            'email'         => $request->email,
            'password_hash' => Hash::make($request->password),
            'role_id'       => 'R001',
            'date_of_birth' => $request->date_of_birth,
            'address'       => $request->address,
            'phone_number'  => $request->phone_number,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'       => 'Đăng ký thành công',
            'user'          => $user,
            'access_token'  => $token,
            'token_type'    => 'Bearer',
        ], 201);
    }

    // Đăng xuất
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Đăng xuất thành công']);
    }


    // FORGOT PASSWORD
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:web_users,email'
        ]);

        $token = Str::random(64);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            [
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]
        );

        return response()->json([
            "success" => true,
            "message" => "Email found. Continue reset password",
            "token" => $token,
            "email" => $request->email
        ]);


    }


    // RESET PASSWORD
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:web_users,email',
            'password' => 'required|string|min:6',
            'token' => 'required'
        ]);

        // 🔍 DEBUG: Kiểm tra token
        \Log::info('Reset password attempt:', [
            'email' => $request->email,
            'token' => $request->token
        ]);

        $tokenData = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        // 🔍 DEBUG: Token có tồn tại không?
        \Log::info('Token data:', ['tokenData' => $tokenData]);

        if (!$tokenData) {
            return response()->json([
                "success" => false,
                "message" => "Invalid token"
            ], 400);
        }

        // 🔍 DEBUG: Update password
        $oldHash = User::where('email', $request->email)->value('password_hash');
        \Log::info('Old password hash:', ['hash' => $oldHash]);

        User::where('email', $request->email)->update([
            'password_hash' => Hash::make($request->password)
        ]);

        $newHash = User::where('email', $request->email)->value('password_hash');
        \Log::info('New password hash:', ['hash' => $newHash]);

        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json([
            "success" => true,
            "message" => "Password updated successfully"
        ]);
    }

}
