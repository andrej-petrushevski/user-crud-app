<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Api-Key') ?? $request->input('api_key');

        if (! $apiKey) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        /** @var User $user */
        $user = User::query()->where('api_key', $apiKey)->first();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        auth()->login($user);

        return $next($request);
    }
}
