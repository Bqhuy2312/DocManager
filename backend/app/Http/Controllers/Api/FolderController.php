<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Folder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FolderController extends Controller
{
    public function index(): JsonResponse
    {
        $folders = Folder::query()
            ->whereNull('parent_id')
            ->with('descendants')
            ->orderBy('name')
            ->get();

        return response()->json($folders);
    }

    public function categories(): JsonResponse
    {
        $folders = Folder::query()
            ->whereNotNull('parent_id')
            ->with('parent:id,name')
            ->withCount('documents')
            ->orderBy('name')
            ->get();

        return response()->json($folders);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'uuid', Rule::exists('folders', 'id')],
        ]);

        $folder = Folder::create($validated);

        return response()->json($folder->load('parent:id,name'), 201);
    }

    public function update(Request $request, Folder $folder): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $folder->update($validated);

        return response()->json($folder->fresh()->load('parent:id,name'));
    }

    public function destroy(Folder $folder): JsonResponse
    {
        if ($folder->documents()->exists() || $folder->children()->exists()) {
            return response()->json([
                'message' => 'Không thể xóa thư mục đang chứa tài liệu hoặc thư mục con.',
            ], 422);
        }

        $folder->delete();

        return response()->json(['message' => 'Đã xóa thư mục.']);
    }
}
