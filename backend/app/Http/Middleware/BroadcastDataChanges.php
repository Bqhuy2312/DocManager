<?php

namespace App\Http\Middleware;

use App\Services\RealtimeDataService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BroadcastDataChanges
{
    private const SCOPES = [
        'documents' => 'document',
        'favorites' => 'document',
        'folders' => 'folder',
        'users' => 'member',
        'departments' => 'department',
        'backups' => 'backup',
        'register' => 'member',
        'me' => 'member',
    ];

    public function __construct(private readonly RealtimeDataService $realtime)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldBroadcast($request, $response)) {
            return $response;
        }

        $routeUri = ltrim($request->route()?->uri() ?? '', '/');
        $resourceUri = str_starts_with($routeUri, 'api/')
            ? substr($routeUri, 4)
            : $routeUri;
        $resource = explode('/', $resourceUri)[0] ?? '';
        $scope = self::SCOPES[$resource] ?? null;

        if (! $scope) {
            return $response;
        }

        $this->realtime->changed($scope, $this->action($request, $routeUri), [
            'entity_id' => $this->entityId($request, $scope),
            'actor_id' => $request->user()?->getAuthIdentifier(),
        ]);

        return $response;
    }

    private function shouldBroadcast(Request $request, Response $response): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            && $response->getStatusCode() >= 200
            && $response->getStatusCode() < 300;
    }

    private function action(Request $request, string $routeUri): string
    {
        if (str_contains($routeUri, '/approval')) {
            return 'approval';
        }

        if (str_contains($routeUri, '/favorite')) {
            return 'favorite';
        }

        if (str_contains($routeUri, '/restore')) {
            return 'restore';
        }

        return match ($request->method()) {
            'POST' => 'created',
            'DELETE' => 'deleted',
            default => 'updated',
        };
    }

    private function entityId(Request $request, string $scope): ?string
    {
        $parameter = match ($scope) {
            'document' => 'document',
            'folder' => 'folder',
            'member' => 'user',
            'department' => 'department',
            'backup' => 'backup',
            default => null,
        };

        if (! $parameter) {
            return null;
        }

        $value = $request->route($parameter);

        return $value instanceof Model ? (string) $value->getKey() : ($value ? (string) $value : null);
    }
}
