<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class Jwt
{
    public const ISSUER = 'https://identity.sia.iti.ac.id';

    public const TTL = 43200;

    /**
     * @param  list<string>  $permissions
     * @return array{token: string, expiresIn: int, claims: array<string, mixed>}
     */
    public static function issue(User $user, array $permissions = []): array
    {
        $now = time();

        $claims = [
            'iss' => self::ISSUER,
            'sub' => $user->user_id,
            'identifier' => $user->user_identifier,
            'roles' => $user->roles->pluck('role_code')->values()->all(),
            'permissions' => $permissions,
            'iat' => $now,
            'exp' => $now + self::TTL,
            'jti' => 'jwt_' . Str::random(16),
        ];

        return ['token' => self::encode($claims), 'expiresIn' => self::TTL, 'claims' => $claims];
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    public static function encode(array $claims): string
    {
        $segments = [
            self::base64Url(json_encode(['typ' => 'JWT', 'alg' => 'HS256'], JSON_THROW_ON_ERROR)),
            self::base64Url(json_encode($claims, JSON_THROW_ON_ERROR)),
        ];

        $signature = hash_hmac('sha256', implode('.', $segments), self::key(), true);
        $segments[] = self::base64Url($signature);

        return implode('.', $segments);
    }

    /**
     * @return array<string, mixed>|null klaim bila tanda tangan, issuer, dan exp sah
     */
    public static function decode(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        $expected = self::base64Url(hash_hmac('sha256', $header . '.' . $payload, self::key(), true));

        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $claims = json_decode(self::base64UrlDecode($payload), true);

        if (! is_array($claims)) {
            return null;
        }

        if (($claims['iss'] ?? null) !== self::ISSUER) {
            return null;
        }

        if ((int) ($claims['exp'] ?? 0) <= time()) {
            return null;
        }

        return $claims;
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    public static function revoke(array $claims): void
    {
        $ttl = max(1, (int) ($claims['exp'] ?? 0) - time());

        Cache::put(self::revokedKey((string) ($claims['jti'] ?? '')), true, $ttl);
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    public static function isRevoked(array $claims): bool
    {
        return Cache::has(self::revokedKey((string) ($claims['jti'] ?? '')));
    }

    private static function revokedKey(string $jti): string
    {
        return 'jwt:revoked:' . $jti;
    }

    private static function key(): string
    {
        $key = (string) config('app.key');

        return str_starts_with($key, 'base64:')
            ? (string) base64_decode(substr($key, 7))
            : $key;
    }

    private static function base64Url(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $segment): string
    {
        return (string) base64_decode(strtr($segment, '-_', '+/'));
    }
}
