<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if ($token === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Intentionally NOT calling withTrashed(): SoftDeletes global scope must
        // exclude soft-deleted clients so revoked tokens stop working immediately.
        $client = ApiClient::where('token_hash', hash('sha256', $token))->first();

        if ($client === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $client->is_active) {
            return response()->json(['message' => 'This API client is disabled.'], 403);
        }

        $client->forceFill(['last_used_at' => now()])->saveQuietly();

        $request->attributes->set('apiClient', $client);

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (! is_string($header) || ! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($header, 7));

        return $token === '' ? null : $token;
    }
}
