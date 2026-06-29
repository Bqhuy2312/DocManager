<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\RealtimeNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (Notification $notification): array => $this->format($notification));

        return response()->json([
            'unread_count' => $request->user()->notifications()->where('is_read', false)->count(),
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        abort_unless((string) $notification->user_id === (string) $request->user()->id, 403);

        $notification->update(['is_read' => true]);
        app(RealtimeNotificationService::class)->notificationStateChanged($request->user());

        return response()->json($this->format($notification->fresh()));
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()
            ->notifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);
        app(RealtimeNotificationService::class)->notificationStateChanged($request->user());

        return response()->json([
            'unread_count' => 0,
        ]);
    }

    public function deleteRead(Request $request): JsonResponse
    {
        $deleted = $request->user()
            ->notifications()
            ->where('is_read', true)
            ->delete();

        app(RealtimeNotificationService::class)->notificationStateChanged($request->user());

        return response()->json([
            'deleted' => $deleted,
            'unread_count' => $request->user()->notifications()->where('is_read', false)->count(),
        ]);
    }

    private function format(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'title' => $notification->title,
            'message' => $notification->message,
            'type' => $notification->type,
            'link' => $notification->link,
            'is_read' => (bool) $notification->is_read,
            'created_at' => $notification->created_at,
        ];
    }
}
