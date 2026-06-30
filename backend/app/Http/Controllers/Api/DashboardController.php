<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Folder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $approvedDocuments = Document::query()->where('status', 'approved');
        $recentDocuments = (clone $approvedDocuments)
            ->with(['folder.parent', 'creator.department', 'tags'])
            ->withExists([
                'favoritedBy as is_favorite' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn (Document $document) => $this->formatDocument($document));

        $favoriteDocuments = Document::query()
            ->with(['folder.parent', 'creator.department', 'tags'])
            ->withExists([
                'favoritedBy as is_favorite' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->where('status', 'approved')
            ->whereHas('favoritedBy', fn ($query) => $query->where('user_id', $user->id))
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn (Document $document) => $this->formatDocument($document));

        $pendingQuery = Document::query()->where('status', 'pending');
        if ($user->role === 'editor') {
            $pendingQuery->where('created_by', $user->id);
        } elseif ($user->role !== 'admin') {
            $pendingQuery->whereRaw('1 = 0');
        }

        return response()->json([
            'stats' => [
                'documents' => (clone $approvedDocuments)->count(),
                'folders' => Folder::query()->count(),
                'favorites' => $user->favorites()->where('status', 'approved')->count(),
                'pending' => $pendingQuery->count(),
                'recent' => (clone $approvedDocuments)->where('updated_at', '>=', now()->subDays(7))->count(),
            ],
            'recent_documents' => $recentDocuments,
            'favorite_documents' => $favoriteDocuments,
            'popular_documents' => $this->popularDocuments(),
            'activities' => ActivityLog::query()
                ->with('user:id,full_name,avatar')
                ->latest()
                ->get()
                ->map(fn (ActivityLog $activity) => $this->formatActivity($activity)),
        ]);
    }

    private function formatDocument(Document $document): array
    {
        return [
            'id' => $document->id,
            'title' => $document->title,
            'description' => $document->description,
            'category' => $document->folder?->name,
            'folder' => $document->folder?->parent?->name,
            'author' => $document->creator?->full_name,
            'department' => $document->creator?->department?->name,
            'status' => $document->status,
            'is_favorite' => (bool) ($document->is_favorite ?? false),
            'file_size' => $document->file_size,
            'tags' => $document->tags->pluck('tag_name')->values(),
            'created_at' => $document->created_at,
            'updated_at' => $document->updated_at,
        ];
    }

    private function popularDocuments()
    {
        $accessRows = ActivityLog::query()
            ->select('target_id')
            ->selectRaw('COUNT(*) as access_count, MAX(created_at) as last_accessed_at')
            ->where('action', 'viewed')
            ->where('target_type', Document::class)
            ->whereNotNull('target_id')
            ->groupBy('target_id')
            ->orderByDesc('access_count')
            ->orderByDesc('last_accessed_at')
            ->limit(5)
            ->get();

        $documents = Document::query()
            ->with(['creator.department'])
            ->whereIn('id', $accessRows->pluck('target_id'))
            ->get()
            ->keyBy('id');

        return $accessRows
            ->map(function ($row) use ($documents): ?array {
                $document = $documents->get($row->target_id);
                if (! $document) {
                    return null;
                }

                return [
                    'id' => $document->id,
                    'title' => $document->title,
                    'author' => $document->creator?->full_name,
                    'department' => $document->creator?->department?->name,
                    'access_count' => (int) $row->access_count,
                    'last_accessed_at' => $row->last_accessed_at,
                ];
            })
            ->filter()
            ->values();
    }

    private function formatActivity(ActivityLog $activity): array
    {
        $metadata = $activity->metadata ?? [];

        return [
            'id' => $activity->id,
            'action' => $activity->action,
            'user_name' => $activity->user?->full_name ?? 'Người dùng',
            'user_avatar' => $activity->user?->avatar,
            'document_id' => $metadata['document_id'] ?? null,
            'document_title' => $metadata['document_title'] ?? null,
            'target_label' => $metadata['document_title'] ?? $metadata['target_label'] ?? 'hệ thống',
            'target_type' => $activity->target_type,
            'created_at' => $activity->created_at,
        ];
    }
}
