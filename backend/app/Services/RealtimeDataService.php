<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class RealtimeDataService
{
    public function changed(string $scope, string $action, array $payload = []): void
    {
        $url = rtrim((string) config('services.realtime.url'), '/');
        $secret = config('services.realtime.secret');

        if (! $url || ! $secret) {
            return;
        }

        try {
            Http::timeout(1.5)
                ->withHeaders(['X-Realtime-Secret' => $secret])
                ->post($url . '/api/data-changes', [
                    'scope' => $scope,
                    'action' => $action,
                    'payload' => $payload,
                    'occurred_at' => now()->toISOString(),
                ]);
        } catch (Throwable) {
            // Realtime synchronization must not block the main API request.
        }
    }
}
