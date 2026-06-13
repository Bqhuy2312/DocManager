<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load('department:id,name');

        return response()->json([
            'user' => $this->userPayload($user),
            'settings' => $this->settingPayload($this->settingsFor($user)),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $user->update($data);

        return response()->json($this->userPayload($user->fresh()->load('department:id,name')));
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'language' => ['sometimes', Rule::in(['vi', 'en'])],
            'auto_save' => ['sometimes', 'boolean'],
            'dark_mode' => ['sometimes', 'boolean'],
            'timezone' => ['sometimes', 'string', 'max:50'],
            'email_enabled' => ['sometimes', 'boolean'],
            'in_app_enabled' => ['sometimes', 'boolean'],
            'notify_upload' => ['sometimes', 'boolean'],
            'notify_edit' => ['sometimes', 'boolean'],
            'notify_approve' => ['sometimes', 'boolean'],
            'notify_system' => ['sometimes', 'boolean'],
            'two_factor_enabled' => ['sometimes', 'boolean'],
            'two_factor_pin' => ['nullable', 'digits:6'],
        ]);

        $settings = $this->settingsFor($request->user());

        if (($data['two_factor_enabled'] ?? false) && !$settings->two_factor_pin_hash && empty($data['two_factor_pin'])) {
            return response()->json([
                'message' => 'Vui lòng nhập mã bảo mật 6 số để bật xác thực hai yếu tố.',
            ], 422);
        }

        if (!empty($data['two_factor_pin'])) {
            $data['two_factor_pin_hash'] = Hash::make($data['two_factor_pin']);
        }

        if (array_key_exists('two_factor_enabled', $data) && !$data['two_factor_enabled']) {
            $data['two_factor_pin_hash'] = null;
        }

        unset($data['two_factor_pin']);

        $settings->update($data);

        return response()->json($this->settingPayload($settings->fresh()));
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = $request->user();

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Mật khẩu hiện tại không đúng.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        return response()->json([
            'message' => 'Đã đổi mật khẩu thành công.',
        ]);
    }

    private function settingsFor($user)
    {
        return $user->settings()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'language' => 'vi',
                'auto_save' => true,
                'dark_mode' => false,
                'timezone' => 'UTC+7',
                'email_enabled' => true,
                'in_app_enabled' => true,
                'notify_upload' => true,
                'notify_edit' => true,
                'notify_approve' => true,
                'notify_system' => true,
                'two_factor_enabled' => false,
            ]
        );
    }

    private function userPayload($user): array
    {
        return [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'position' => $user->position,
            'email' => $user->email,
            'role' => $user->role,
            'avatar' => $user->avatar,
            'department_id' => $user->department_id,
            'department' => $user->department,
        ];
    }

    private function settingPayload($settings): array
    {
        return [
            'language' => $settings->language,
            'auto_save' => (bool) $settings->auto_save,
            'dark_mode' => (bool) $settings->dark_mode,
            'timezone' => $settings->timezone,
            'email_enabled' => (bool) $settings->email_enabled,
            'in_app_enabled' => (bool) $settings->in_app_enabled,
            'notify_upload' => (bool) $settings->notify_upload,
            'notify_edit' => (bool) $settings->notify_edit,
            'notify_approve' => (bool) $settings->notify_approve,
            'notify_system' => (bool) $settings->notify_system,
            'two_factor_enabled' => (bool) $settings->two_factor_enabled,
        ];
    }
}
