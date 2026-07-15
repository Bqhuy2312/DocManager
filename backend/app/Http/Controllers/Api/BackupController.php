<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\BackupRestoreService;
use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function index(): JsonResponse
    {
        $backups = Backup::query()
            ->with('creator:id,full_name')
            ->latest()
            ->get()
            ->map(fn (Backup $backup): array => $this->format($backup));

        return response()->json($backups);
    }

    public function store(Request $request, BackupService $backupService): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['database', 'full'])],
        ]);

        $backup = Backup::create([
            'created_by' => $request->user()->id,
            'type' => $validated['type'],
            'status' => 'pending',
        ]);

        $backup = $backupService->run($backup);

        return response()->json($this->format($backup), $backup->status === 'success' ? 201 : 500);
    }

    public function restore(Request $request, BackupService $backupService, BackupRestoreService $restoreService): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:512000'],
        ]);

        $safetyBackup = Backup::create([
            'created_by' => $request->user()->id,
            'type' => 'database',
            'status' => 'pending',
            'message' => 'Auto backup before database restore.',
        ]);

        $safetyBackup = $backupService->run($safetyBackup);
        if ($safetyBackup->status !== 'success') {
            return response()->json([
                'message' => 'Không thể tạo backup an toàn trước khi import.',
                'backup' => $this->format($safetyBackup),
            ], 500);
        }

        $result = $restoreService->restore($validated['file']);

        return response()->json([
            'message' => 'Import backup thành công. Bạn có thể cần đăng nhập lại nếu dữ liệu tài khoản/token đã thay đổi.',
            'executed_statements' => $result['executed_statements'],
            'safety_backup' => $this->format($safetyBackup),
        ]);
    }

    public function download(Backup $backup): BinaryFileResponse
    {
        abort_unless($backup->status === 'success' && $backup->file_path, 404);

        $path = storage_path('app/' . $backup->file_path);
        abort_unless(File::exists($path), 404);

        return response()->download($path, $backup->file_name);
    }

    public function destroy(Backup $backup): JsonResponse
    {
        if ($backup->file_path) {
            $path = storage_path('app/' . $backup->file_path);
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        $backup->delete();

        return response()->json(['message' => 'Backup deleted successfully.']);
    }

    private function format(Backup $backup): array
    {
        return [
            'id' => $backup->id,
            'type' => $backup->type,
            'status' => $backup->status,
            'file_name' => $backup->file_name,
            'file_size' => $backup->file_size,
            'documents_count' => $backup->documents_count,
            'versions_count' => $backup->versions_count,
            'avatars_count' => $backup->avatars_count,
            'message' => $backup->message,
            'created_by' => $backup->creator?->full_name,
            'started_at' => $backup->started_at,
            'finished_at' => $backup->finished_at,
            'created_at' => $backup->created_at,
        ];
    }
}
