<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSystemApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = env('SYSTEM_API_KEY');
        
        if (empty($expectedKey)) {
            return response()->json(['error' => 'System API Key is not configured on the server.'], 500);
        }

        $providedKey = $request->header('X-API-KEY') ?? $request->bearerToken();

        if ($providedKey !== $expectedKey) {
            return response()->json(['error' => 'Unauthorized. Invalid API Key.'], 401);
        }

        return $next($request);
    }
}
