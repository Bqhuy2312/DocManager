<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return response()->json([
            'users' => User::query()
                ->with('department:id,name')
                ->withCount('documents')
                ->orderBy('full_name')
                ->get(),
            'departments' => Department::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'role' => ['required', Rule::in(['admin', 'editor', 'viewer'])],
        ]);

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return response()->json(
            $user->load('department:id,name')->loadCount('documents'),
            201
        );
    }

    public function show(User $user)
    {
        return response()->json(
            $user->load('department:id,name')->loadCount('documents')
        );
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:6'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'role' => ['required', Rule::in(['admin', 'editor', 'viewer'])],
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json(
            $user->fresh()->load('department:id,name')->loadCount('documents')
        );
    }

    public function destroy(Request $request, User $user)
    {
        if ((string) $request->user()->id === (string) $user->id) {
            return response()->json([
                'message' => 'Không thể xóa tài khoản đang đăng nhập.',
            ], 422);
        }

        if ($user->documents()->exists()) {
            return response()->json([
                'message' => 'Không thể xóa thành viên đã tạo tài liệu. Hãy giữ tài khoản này để bảo toàn lịch sử tài liệu.',
            ], 409);
        }

        $user->approvedDocuments()->update([
            'approved_by' => null,
        ]);

        $user->tokens()->delete();
        $user->delete();

        return response()->noContent();
    }
}
