<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuestIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->is_guest && $user->guest_expires_at && $user->guest_expires_at->isPast()) {
            $user->tokens()->delete();
            $user->favorites()->detach();
            $user->notifications()->delete();
            $user->activityLogs()->delete();
            $user->delete();

            return response()->json([
                'message' => 'Phiên người xem đã hết hạn. Vui lòng đăng nhập lại.',
            ], 401);
        }

        return $next($request);
    }
}
