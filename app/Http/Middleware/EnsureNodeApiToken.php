<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNodeApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $configuredToken = (string) config('ai_assistant.node_api_token');

        if ($configuredToken === '') {
            return response()->json(['message' => 'Node API token not configured'], Response::HTTP_FORBIDDEN);
        }

        $token = (string) $request->bearerToken();

        if ($token === '' || ! hash_equals($configuredToken, $token)) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
