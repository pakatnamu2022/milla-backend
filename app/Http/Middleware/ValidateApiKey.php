<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $authorization = $request->header('Authorization');

        if (!$authorization || !str_starts_with($authorization, 'ApiKey ')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $providedKey = substr($authorization, 7); // Remove "ApiKey " prefix
        $validKey = config('app.external_api_key');

        if (!$validKey || !hash_equals($validKey, $providedKey)) {
            return response()->json(['error' => 'Invalid API Key'], 401);
        }

        return $next($request);
    }
}
