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

    public function register(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'department_id' => ['required', 'exists:departments,id'],
        ]);

        $user = User::create([
            'department_id' => $data['department_id'],
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'viewer',
            'is_guest' => false,
        ]);

        $user->settings()->create([
            'language' => 'vi',
            'auto_save' => true,
            'dark_mode' => false,
            'timezone' => 'Asia/Ho_Chi_Minh',
            'email_enabled' => false,
            'in_app_enabled' => true,
            'notify_upload' => true,
            'notify_edit' => true,
            'notify_approve' => true,
            'notify_system' => true,
            'two_factor_enabled' => false,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;
        $this->logAuthActivity($request, $user, 'register');

        return response()->json([
            'token' => $token,
            'user' => $user->load('department'),
        ], 201);
    }

    public function guestLogin(Request $request)
    {
        $data = $request->validate([
            'guest_device_id' => ['required', 'string', 'max:100'],
        ]);

        $expiresAt = now()->addDays(30);
        $existingGuest = User::query()
            ->where('is_guest', true)
            ->where('guest_device_id', $data['guest_device_id'])
            ->where(function ($query): void {
                $query
                    ->whereNull('guest_expires_at')
                    ->orWhere('guest_expires_at', '>', now());
            })
            ->latest()
            ->first();

        if ($existingGuest) {
            $existingGuest->update([
                'guest_expires_at' => $expiresAt,
            ]);

            $token = $existingGuest->createToken('guest_token')->plainTextToken;
            $this->logAuthActivity($request, $existingGuest, 'guest_login');

            return response()->json([
                'token' => $token,
                'user' => $existingGuest->fresh(),
            ]);
        }

        $guest = User::create([
            'full_name' => 'Người xem ' . now()->format('His'),
            'email' => 'guest_' . Str::uuid() . '@guest.local',
            'password' => Hash::make(Str::random(32)),
            'role' => 'viewer',
            'is_guest' => true,
            'guest_expires_at' => $expiresAt,
            'guest_device_id' => $data['guest_device_id'],
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
