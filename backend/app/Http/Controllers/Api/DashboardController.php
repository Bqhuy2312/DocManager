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

        $visibleDocuments = $this->visibleDocuments($request);
        $recentDocuments = (clone $visibleDocuments)
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
                'documents' => (clone $visibleDocuments)->count(),
                'folders' => Folder::query()->count(),
                'favorites' => $user->favorites()->where('status', 'approved')->count(),
                'pending' => $pendingQuery->count(),
                'recent' => (clone $visibleDocuments)->where('updated_at', '>=', now()->subDays(7))->count(),
            ],
            'recent_documents' => $recentDocuments,
            'favorite_documents' => $favoriteDocuments,
            'activities' => ActivityLog::query()
                ->with('user:id,full_name,avatar')
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (ActivityLog $activity) => $this->formatActivity($activity)),
        ]);
    }

    private function visibleDocuments(Request $request)
    {
        $user = $request->user();

        return Document::query()
            ->when($user->role === 'admin', fn ($query) => $query)
            ->when($user->role === 'viewer', fn ($query) => $query->where('status', 'approved'))
            ->when($user->role === 'editor', function ($query) use ($user): void {
                $query->where(function ($query) use ($user): void {
                    $query
                        ->where('status', 'approved')
                        ->orWhere('created_by', $user->id);
                });
            });
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
            'status' => $document->status,
            'is_favorite' => (bool) ($document->is_favorite ?? false),
            'file_size' => $document->file_size,
            'tags' => $document->tags->pluck('tag_name')->values(),
            'created_at' => $document->created_at,
            'updated_at' => $document->updated_at,
        ];
    }

    private function formatActivity(ActivityLog $activity): array
    {
        $metadata = $activity->metadata ?? [];

        return [
            'id' => $activity->id,
            'action' => $activity->action,
            'user_name' => $activity->user?->full_name ?? 'Người dùng',
            'user_avatar' => $activity->user?->avatar,
            'document_id' => $metadata['document_id'] ?? $activity->target_id,
            'document_title' => $metadata['document_title'] ?? 'tài liệu',
            'created_at' => $activity->created_at,
        ];
    }
}
