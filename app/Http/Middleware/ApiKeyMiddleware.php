<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-API-Key') ?? $request->input('api_key');

        if (!$key) {
            return response()->json([
                'success' => false,
                'message' => 'API Key is missing.'
            ], 401);
        }

        $apiKeyRecord = ApiKey::where('key', $key)->first();

        if (!$apiKeyRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API Key provided.'
            ], 401);
        }

        $apiKeyRecord->update(['last_used_at' => now()]);

        $request->attributes->set('api_key', $apiKeyRecord);

        return $next($request);
    }
}
