<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\AuditLog;
use App\Support\Jwt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * FR-001. Endpoint login, logout, dan ganti kata sandi (kontrak bagian 6.1).
 */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'NIM/NIDN wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $user = User::with('roles')
            ->where('user_identifier', $credentials['username'])
            ->first();

        if ($user === null || ! $user->user_status || ! Hash::check($credentials['password'], $user->user_password)) {
            AuditLog::record(AuditLog::LOGIN_FAILED, [
                'identifier' => $credentials['username'],
                'ip' => $request->ip(),
            ], $user);

            return ApiResponse::error(
                401,
                ApiResponse::AUTHENTICATION_FAILED,
                'Invalid username or password',
                [['field' => 'password', 'message' => 'Kombinasi NIM/NIDN dan kata sandi tidak valid.']],
            );
        }

        $issued = Jwt::issue($user);

        AuditLog::record(AuditLog::LOGIN_SUCCEEDED, [
            'ip' => $request->ip(),
            'roles' => $user->roles->pluck('role_code')->values()->all(),
        ], $user);

        return ApiResponse::success([
            'tokenType' => 'Bearer',
            'accessToken' => $issued['token'],
            'expiresIn' => $issued['expiresIn'],
            'user' => [
                'userId' => $user->user_id,
                'identifier' => $user->user_identifier,
                'fullName' => $user->user_full_name,
                'roles' => $user->roles->pluck('role_code')->values()->all(),
            ],
        ], 'Authentication successful');
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var array<string, mixed> $claims */
        $claims = $request->attributes->get('jwt_claims');

        Jwt::revoke($claims);

        return ApiResponse::success(null, 'Successfully logged out');
    }

    public function changePassword(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', 'min:8'],
            'newPasswordConfirmation' => ['required', 'same:newPassword'],
        ], [
            'currentPassword.required' => 'Kata sandi saat ini wajib diisi.',
            'newPassword.min' => 'Kata sandi baru minimal 8 karakter.',
            'newPasswordConfirmation.same' => 'Konfirmasi kata sandi baru tidak sama.',
        ]);

        /** @var User $user */
        $user = $request->user();

        if (! Hash::check($payload['currentPassword'], $user->user_password)) {
            AuditLog::record(AuditLog::PASSWORD_CHANGE_FAILED, ['ip' => $request->ip()], $user);

            return ApiResponse::error(
                422,
                ApiResponse::VALIDATION_ERROR,
                'Password validation failed',
                [['field' => 'currentPassword', 'message' => 'Kata sandi saat ini tidak sesuai.']],
            );
        }

        $user->update(['user_password' => $payload['newPassword']]);

        AuditLog::record(AuditLog::PASSWORD_CHANGED, ['ip' => $request->ip()], $user);

        return ApiResponse::success(null, 'Password updated successfully');
    }
}
