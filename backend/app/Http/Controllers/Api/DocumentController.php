<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Folder;
use App\Services\CloudinaryService;
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
            ->when(
                $request->user()->role === 'admin' && in_array($status, ['approved', 'pending'], true),
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

        return response()->json($this->format(
            $document->load(['folder.parent', 'creator.department', 'approver', 'tags'])
        ));
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
            $this->format($document->load(['folder.parent', 'creator.department', 'approver', 'tags'])),
            201
        );
    }

    public function download(Request $request, Document $document): RedirectResponse
    {
        abort_unless($this->canView($request, $document), 403);

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

        return response()->json($this->format(
            $document->load(['folder.parent', 'creator.department', 'approver', 'tags'])
        ));
    }

    public function destroy(Document $document): JsonResponse
    {
        try {
            $this->cloudinary->destroyDocument($document->cloudinary_public_id);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

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
            'approved_by' => $document->approver?->full_name,
            'file_name' => $document->file_name,
            'file_path' => $document->file_path,
            'file_size' => $document->file_size,
            'mime_type' => $document->mime_type,
            'version' => $document->version,
            'status' => $document->status,
            'tags' => $document->tags->pluck('tag_name')->values(),
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
}
