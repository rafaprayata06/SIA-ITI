<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware role:ADMIN_ACADEMIC,LECTURER. Pengguna yang tidak punya satu pun
 * dari role tersebut dibalas 403 FORBIDDEN_ACCESS.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $owned = $user?->roles->pluck('role_code')->all() ?? [];

        if ($user === null || array_intersect($roles, $owned) === []) {
            return ApiResponse::error(
                403,
                ApiResponse::FORBIDDEN_ACCESS,
                'You are not allowed to access this resource',
            );
        }

        return $next($request);
    }
}
