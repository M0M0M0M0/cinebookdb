<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Kiểm tra xem user có phải là staff không
        if (!$user || get_class($user) !== 'App\Models\Staff') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        // Kiểm tra role có phải ADMIN hoặc MANAGER không
        if (!in_array($user->role_id, ['ADMIN', 'MANAGER'])) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Insufficient permissions.'
            ], 403);
        }

        return $next($request);
    }
}