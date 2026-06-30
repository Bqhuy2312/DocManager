<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\Notification;
use App\Models\User;
use App\Services\CloudinaryService;
use App\Services\ActivityLoggerService;
use App\Services\RealtimeNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class DocumentController extends Controller
{
    public function __construct(private readonly CloudinaryService $cloudinary)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $documents = Document::query()
            ->with(['folder.parent', 'creator.department', 'approver', 'tags'])
            ->withExists([
                'favoritedBy as is_favorite' => fn ($query) => $query->where('user_id', $request->user()->id),
            ])
            ->when(
                $request->user()->role === 'admin' && in_array($status, ['approved', 'pending', 'rejected'], true),
                fn ($query) => $query->where('status', $status),
                function ($query): void {
                    $query->where('status', 'approved');
                }
            )
            ->when($request->user()->role === 'viewer', function ($query): void {
                $query->where('status', 'approved');
            })
            ->when($request->user()->role === 'editor', function ($query) use ($request): void {
                $query->where(function ($query) use ($request): void {
                    $query
                        ->where('status', 'approved')
                        ->orWhere('created_by', $request->user()->id);
                });
            })
            ->latest()
            ->get()
            ->map(fn (Document $document) => $this->format($document));

        return response()->json($documents);
    }

    public function show(Request $request, Document $document): JsonResponse
    {
        abort_unless($this->canView($request, $document), 403);

        $this->logActivity($request, 'viewed', $document);

        return response()->json($this->format(
            $document
                ->load(['folder.parent', 'creator.department', 'approver', 'tags', 'versions.updater'])
                ->loadExists([
                    'favoritedBy as is_favorite' => fn ($query) => $query->where('user_id', $request->user()->id),
                ])
        ));
    }

    public function favorites(Request $request): JsonResponse
    {
        $documents = Document::query()
            ->with(['folder.parent', 'creator.department', 'approver', 'tags'])
            ->withExists([
                'favoritedBy as is_favorite' => fn ($query) => $query->where('user_id', $request->user()->id),
            ])
            ->where('status', 'approved')
            ->whereHas('favoritedBy', fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()
            ->get()
            ->map(fn (Document $document) => $this->format($document));

        return response()->json($documents);
    }

    public function metadata(): JsonResponse
    {
        return response()->json([
            'folders' => Folder::query()
                ->whereNotNull('parent_id')
                ->with('parent:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'parent_id']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'folder_id' => [
                'required',
                'uuid',
                Rule::exists('folders', 'id')->whereNotNull('parent_id'),
            ],
            'tags' => ['nullable', 'string'],
            'file' => ['required', 'file', 'max:102400'],
        ]);

        try {
            $upload = $this->cloudinary->uploadDocument($request->file('file'));
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        try {
            $document = DB::transaction(function () use ($request, $validated, $upload): Document {
                $file = $request->file('file');
                $document = Document::create([
                    'folder_id' => $validated['folder_id'],
                    'created_by' => $request->user()->id,
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $upload['url'],
                    'cloudinary_public_id' => $upload['public_id'],
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'version' => '1.0',
                    'status' => 'pending',
                ]);

                $tags = collect(explode(',', $validated['tags'] ?? ''))
                    ->map(fn (string $tag) => trim($tag))
                    ->filter()
                    ->unique();

                $document->tags()->createMany(
                    $tags->map(fn (string $tag) => ['tag_name' => $tag])->all()
                );

                return $document;
            });
        } catch (\Throwable $exception) {
            $this->cloudinary->destroyDocument($upload['public_id']);
            throw $exception;
        }

        return response()->json(
            tap($this->format($document->load(['folder.parent', 'creator.department', 'approver', 'tags'])), function () use ($request, $document): void {
                $this->logActivity($request, 'uploaded', $document);
                $this->notifyAdminsAboutUpload($request, $document);
            }),
            201
        );
    }

    public function download(Request $request, Document $document): RedirectResponse
    {
        abort_unless($this->canView($request, $document), 403);

        $this->logActivity($request, 'downloaded', $document);

        return redirect()->away($document->file_path);
    }

    public function approve(Request $request, Document $document): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
        ]);

        $document->update([
            'approved_by' => $request->user()->id,
            'status' => $validated['status'],
        ]);

        $this->logActivity($request, $validated['status'] === 'approved' ? 'approved' : 'rejected', $document);
        $this->notifyCreatorAboutApproval($request, $document, $validated['status']);

        return response()->json($this->format(
            $document->load(['folder.parent', 'creator.department', 'approver', 'tags'])
        ));
    }

    public function updateFile(Request $request, Document $document): JsonResponse
    {
        abort_unless($this->canUpdate($request, $document), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'tags' => ['nullable', 'string'],
            'file' => ['required', 'file', 'max:102400'],
        ]);

        try {
            $upload = $this->cloudinary->uploadDocument($request->file('file'));
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        $oldPublicId = $document->cloudinary_public_id;

        try {
            $document = DB::transaction(function () use ($request, $document, $validated, $upload): Document {
                $file = $request->file('file');
                $nextVersion = $this->nextVersion($document->version);

                $document->update([
                    'approved_by' => null,
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $upload['url'],
                    'cloudinary_public_id' => $upload['public_id'],
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'version' => $nextVersion,
                    'status' => 'pending',
                ]);

                $document->tags()->delete();

                $tags = collect(explode(',', $validated['tags'] ?? ''))
                    ->map(fn (string $tag) => trim($tag))
                    ->filter()
                    ->unique();

                $document->tags()->createMany(
                    $tags->map(fn (string $tag) => ['tag_name' => $tag])->all()
                );

                DocumentVersion::create([
                    'document_id' => $document->id,
                    'updated_by' => $request->user()->id,
                    'version' => $nextVersion,
                    'title' => $document->title,
                    'description' => $document->description,
                    'file_name' => $document->file_name,
                    'file_path' => $document->file_path,
                    'cloudinary_public_id' => $document->cloudinary_public_id,
                    'file_size' => $document->file_size,
                    'mime_type' => $document->mime_type,
                ]);

                return $document->fresh();
            });
        } catch (\Throwable $exception) {
            $this->cloudinary->destroyDocument($upload['public_id']);
            throw $exception;
        }

        try {
            $this->cloudinary->destroyDocument($oldPublicId);
        } catch (RuntimeException) {
            // The new version is already active; stale cleanup can be retried later.
        }

        $this->logActivity($request, 'updated', $document);
        $this->notifyAdminsAboutDocumentUpdate($request, $document);

        return response()->json($this->format(
            $document->load(['folder.parent', 'creator.department', 'approver', 'tags', 'versions.updater'])
        ));
    }

    public function toggleFavorite(Request $request, Document $document): JsonResponse
    {
        abort_unless($this->canView($request, $document), 403);

        $relation = $request->user()->favorites();
        $isFavorite = $relation->where('document_id', $document->id)->exists();

        if ($isFavorite) {
            $relation->detach($document->id);
        } else {
            $relation->attach($document->id, ['created_at' => now()]);
        }

        $this->logActivity($request, $isFavorite ? 'unfavorited' : 'favorited', $document);

        return response()->json([
            'is_favorite' => ! $isFavorite,
        ]);
    }

    public function destroy(Request $request, Document $document): JsonResponse
    {
        try {
            $this->cloudinary->destroyDocument($document->cloudinary_public_id);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        $this->logActivity($request, 'deleted', $document);

        $document->delete();

        return response()->json(['message' => 'Document deleted successfully.']);
    }

    private function format(Document $document): array
    {
        return [
            'id' => $document->id,
            'title' => $document->title,
            'description' => $document->description,
            'category' => $document->folder?->name,
            'folder' => $document->folder?->parent?->name,
            'folder_id' => $document->folder_id,
            'department' => $document->creator?->department?->name,
            'author' => $document->creator?->full_name,
            'created_by' => $document->created_by,
            'approved_by' => $document->approver?->full_name,
            'file_name' => $document->file_name,
            'file_path' => $document->file_path,
            'file_size' => $document->file_size,
            'mime_type' => $document->mime_type,
            'version' => $document->version,
            'status' => $document->status,
            'is_favorite' => (bool) ($document->is_favorite ?? false),
            'tags' => $document->tags->pluck('tag_name')->values(),
            'versions' => $document->relationLoaded('versions')
                ? $document->versions
                    ->sortByDesc('created_at')
                    ->values()
                    ->map(fn (DocumentVersion $version): array => [
                        'id' => $version->id,
                        'version' => $version->version,
                        'title' => $version->title,
                        'file_name' => $version->file_name,
                        'file_size' => $version->file_size,
                        'mime_type' => $version->mime_type,
                        'updated_by' => $version->updater?->full_name,
                        'created_at' => $version->created_at,
                    ])
                : [],
            'created_at' => $document->created_at,
            'updated_at' => $document->updated_at,
        ];
    }

    private function canView(Request $request, Document $document): bool
    {
        return $request->user()->role === 'admin'
            || $document->status === 'approved'
            || ($request->user()->role === 'editor' && $document->created_by === $request->user()->id);
    }

    private function canUpdate(Request $request, Document $document): bool
    {
        return $request->user()->role === 'admin'
            || ($request->user()->role === 'editor' && (string) $document->created_by === (string) $request->user()->id);
    }

    private function nextVersion(?string $version): string
    {
        preg_match('/\d+(?:\.\d+)?/', (string) ($version ?: '1.0'), $matches);
        $current = (float) ($matches[0] ?? '1.0');

        return number_format($current + 1, 1, '.', '');
    }

    private function logActivity(Request $request, string $action, Document $document): void
    {
        app(ActivityLoggerService::class)->log(
            $request,
            $action,
            $document,
            [
                'document_id' => $document->id,
                'document_title' => $document->title,
            ]
        );
    }

    private function notifyAdminsAboutUpload(Request $request, Document $document): void
    {
        User::query()
            ->where('role', 'admin')
            ->where('id', '!=', $request->user()->id)
            ->with('settings')
            ->get()
            ->each(function (User $admin) use ($request, $document): void {
                $settings = $admin->settings;

                if ($settings && $settings->notify_upload === false) {
                    return;
                }

                $this->sendNotification(
                    $admin,
                    'Tài liệu mới chờ phê duyệt',
                    $request->user()->full_name . ' đã tải lên "' . $document->title . '".',
                    'upload',
                    '/approvals'
                );
            });
    }

    private function notifyAdminsAboutDocumentUpdate(Request $request, Document $document): void
    {
        User::query()
            ->where('role', 'admin')
            ->where('id', '!=', $request->user()->id)
            ->with('settings')
            ->get()
            ->each(function (User $admin) use ($request, $document): void {
                $settings = $admin->settings;

                if ($settings && $settings->notify_edit === false) {
                    return;
                }

                $this->sendNotification(
                    $admin,
                    'Tài liệu vừa được cập nhật',
                    $request->user()->full_name . ' đã cập nhật "' . $document->title . '" lên phiên bản ' . $document->version . '.',
                    'edit',
                    '/approvals'
                );
            });
    }

    private function notifyCreatorAboutApproval(Request $request, Document $document, string $status): void
    {
        $creator = $document->creator()->with('settings')->first();

        if (! $creator || (string) $creator->id === (string) $request->user()->id) {
            return;
        }

        $settings = $creator->settings;

        if ($settings && $settings->notify_approve === false) {
            return;
        }

        $approved = $status === 'approved';

        $this->sendNotification(
            $creator,
            $approved ? 'Tài liệu đã được phê duyệt' : 'Tài liệu đã bị từ chối',
            '"' . $document->title . '" ' . ($approved ? 'đã được phê duyệt.' : 'đã bị từ chối.'),
            $approved ? 'approved' : 'rejected',
            '/documents/' . $document->id
        );
    }

    private function sendNotification(User $user, string $title, string $message, string $type, ?string $link = null): void
    {
        $settings = $user->settings;

        if (! $settings || $settings->in_app_enabled) {
            $notification = Notification::create([
                'user_id' => $user->id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'link' => $link,
                'is_read' => false,
            ]);

            app(RealtimeNotificationService::class)->notificationCreated($user, $notification);
        }
    }
}
