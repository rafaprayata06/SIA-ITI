<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ApiResponse;
use App\Support\Jwt;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware api.auth: request wajib membawa Bearer token yang sah.
 * Role diambil ulang dari database tiap request, jadi perubahan role
 * langsung berlaku tanpa menunggu token kedaluwarsa.
 */
class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $claims = $token !== null ? Jwt::decode($token) : null;

        if ($claims === null || Jwt::isRevoked($claims)) {
            return $this->unauthorized();
        }

        $user = User::with('roles')->find($claims['sub'] ?? null);

        if ($user === null || ! $user->user_status) {
            return $this->unauthorized();
        }

        $request->setUserResolver(fn () => $user);
        $request->attributes->set('jwt_claims', $claims);

        return $next($request);
    }

    private function unauthorized(): Response
    {
        return ApiResponse::error(
            401,
            ApiResponse::UNAUTHORIZED_TOKEN,
            'Token is missing or already invalidated',
        );
    }
}
