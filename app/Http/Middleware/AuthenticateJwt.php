<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJwt
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $jwt = app(JwtTokenService::class);
        $claims = $jwt->decode($token);

        if (! $claims || $jwt->isRevoked($claims->jti)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = User::query()->find($claims->sub);

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->attributes->set('jwt_jti', $claims->jti);
        $request->attributes->set('jwt_exp', (int) $claims->exp);
        $request->setUserResolver(static fn () => $user);

        return $next($request);
    }
}
