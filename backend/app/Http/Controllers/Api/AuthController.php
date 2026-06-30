<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Services\CloudinaryService;
use RuntimeException;

class AuthController extends Controller
{
    public function __construct(private readonly CloudinaryService $cloudinary)
    {
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'two_factor_code' => ['nullable', 'digits:6'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Email không tồn tại'
            ], 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Sai mật khẩu'
            ], 401);
        }

        $settings = $user->settings;

        if ($settings?->two_factor_enabled) {
            if (!$request->filled('two_factor_code')) {
                return response()->json([
                    'message' => 'Tài khoản này đã bật xác thực hai yếu tố.',
                    'requires_2fa' => true,
                ], 423);
            }

            if (!$settings->two_factor_pin_hash || !Hash::check($request->two_factor_code, $settings->two_factor_pin_hash)) {
                return response()->json([
                    'message' => 'Mã xác thực hai yếu tố không đúng.',
                    'requires_2fa' => true,
                ], 401);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $this->logAuthActivity($request, $user, 'login');

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }

    public function guestLogin(Request $request)
    {
        $guest = User::create([
            'full_name' => 'Người xem ' . now()->format('His'),
            'email' => 'guest_' . Str::uuid() . '@guest.local',
            'password' => Hash::make(Str::random(32)),
            'role' => 'viewer',
            'is_guest' => true,
            'guest_expires_at' => now()->addDays(7),
        ]);

        $token = $guest->createToken('guest_token')->plainTextToken;
        $this->logAuthActivity($request, $guest, 'guest_login');

        return response()->json([
            'token' => $token,
            'user' => $guest,
        ], 201);
    }

    public function logout(Request $request)
    {
        $this->logAuthActivity($request, $request->user(), 'logout');
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Đăng xuất thành công'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(
            $request->user()
        );
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:5120'],
        ]);

        try {
            $upload = $this->cloudinary->uploadAvatar($request->file('avatar'));
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        $user = $request->user();
        $oldPublicId = $user->avatar_public_id;

        $user->update([
            'avatar' => $upload['url'],
            'avatar_public_id' => $upload['public_id'],
        ]);

        try {
            $this->cloudinary->destroyAvatar($oldPublicId);
        } catch (RuntimeException) {
            // The new avatar is already stored; stale cleanup can be retried later.
        }

        return response()->json($user->fresh());
    }

    private function logAuthActivity(Request $request, User $user, string $action): void
    {
        app(ActivityLoggerService::class)->logForUser(
            $user,
            $action,
            $user,
            [
                'target_label' => 'hệ thống',
                'email' => $user->email,
                'role' => $user->role,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );
    }
}
