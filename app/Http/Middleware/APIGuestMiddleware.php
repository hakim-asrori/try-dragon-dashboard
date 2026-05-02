<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Closure;

class APIGuestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->header('Authorization')) {
            $token = explode(" ", $request->header('Authorization'))[1];
            if ($token != "null" && app('auth')->guard('api')) {
                $request->merge(['user' => auth('api')->user()]);
                return $next($request);
            } elseif ($request->guest_id) {
                return $next($request);
            }
        } elseif ($request->guest_id) {
            return $next($request);
        }

        return response()->json([
            'errors' => [
                ['code' => 'auth-001', 'message' => 'Unauthorized.']
            ]
        ], 401);
    }
}
