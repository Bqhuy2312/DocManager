<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

class ActivityLoggerService
{
    public function log(Request $request, string $action, ?Model $target = null, array $metadata = []): ActivityLog
    {
        return $this->logForUser($request->user(), $action, $target, $metadata);
    }

    public function logForUser(User $user, string $action, ?Model $target = null, array $metadata = []): ActivityLog
    {
        $activity = ActivityLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'target_type' => $target ? $target::class : User::class,
            'target_id' => $target?->getKey() ?? $user->id,
            'metadata' => $metadata,
        ]);

        $this->broadcast($activity->load('user:id,full_name,avatar'));

        return $activity;
    }

    public function format(ActivityLog $activity): array
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

    private function broadcast(ActivityLog $activity): void
    {
        $url = rtrim((string) config('services.realtime.url'), '/');
        $secret = config('services.realtime.secret');

        if (! $url || ! $secret) {
            return;
        }

        try {
            Http::timeout(1.5)
                ->withHeaders(['X-Realtime-Secret' => $secret])
                ->post($url . '/api/activities', [
                    'activity' => $this->format($activity),
                ]);
        } catch (Throwable) {
            // Realtime activity should never block the main Laravel request.
        }
    }
}
