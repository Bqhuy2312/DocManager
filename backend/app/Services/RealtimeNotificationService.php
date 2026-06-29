<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Throwable;

class RealtimeNotificationService
{
    public function notificationCreated(User $user, Notification $notification): void
    {
        $this->post('/api/notifications', [
            'user_id' => $user->id,
            'event' => 'notification:new',
            'unread_count' => $user->notifications()->where('is_read', false)->count(),
            'notification' => $this->format($notification),
        ]);
    }

    public function notificationStateChanged(User $user): void
    {
        $this->post('/api/notifications/state', [
            'user_id' => $user->id,
            'unread_count' => $user->notifications()->where('is_read', false)->count(),
        ]);
    }

    private function post(string $path, array $payload): void
    {
        $url = rtrim((string) config('services.realtime.url'), '/');
        $secret = config('services.realtime.secret');

        if (! $url || ! $secret) {
            return;
        }

        try {
            Http::timeout(1.5)
                ->withHeaders(['X-Realtime-Secret' => $secret])
                ->post($url . $path, $payload);
        } catch (Throwable) {
            // Realtime should never block the main Laravel request.
        }
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
