<?php

namespace App\Services;

use App\Models\User;
use DomainException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

class JwtTokenService
{
    public function issue(User $user): array
    {
        $now = time();
        $expiresAt = $now + ((int) config('commerce.jwt_ttl_minutes') * 60);
        $tokenId = bin2hex(random_bytes(16));

        $token = JWT::encode([
            'iss' => config('commerce.jwt_issuer'),
            'sub' => (string) $user->getKey(),
            'iat' => $now,
            'exp' => $expiresAt,
            'jti' => $tokenId,
        ], $this->secret(), 'HS256');

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expiresAt - $now,
        ];
    }

    public function decode(string $token): ?object
    {
        try {
            $claims = JWT::decode($token, new Key($this->secret(), 'HS256'));
        } catch (DomainException|InvalidArgumentException|UnexpectedValueException) {
            return null;
        }

        if (
            ! isset($claims->sub, $claims->jti, $claims->exp)
            || ($claims->iss ?? null) !== config('commerce.jwt_issuer')
            || ! ctype_digit((string) $claims->sub)
            || ! is_string($claims->jti)
            || ! is_numeric($claims->exp)
        ) {
            return null;
        }

        return $claims;
    }

    public function revoke(string $tokenId, int $expiresAt): void
    {
        $ttl = max(0, $expiresAt - time());

        if ($ttl > 0) {
            cache()->put($this->revocationKey($tokenId), true, Carbon::now()->addSeconds($ttl));
        }
    }

    public function isRevoked(string $tokenId): bool
    {
        return cache()->has($this->revocationKey($tokenId));
    }

    private function secret(): string
    {
        $secret = (string) config('commerce.jwt_secret');

        if (strlen($secret) < 32) {
            throw new RuntimeException('JWT_SECRET must be set to at least 32 characters.');
        }

        return $secret;
    }

    private function revocationKey(string $tokenId): string
    {
        return 'jwt:revoked:'.$tokenId;
    }
}
