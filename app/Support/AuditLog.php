<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Throwable;

/**
 * Pencatat activity_logs untuk kejadian keamanan (BR-007, SEC-007).
 * Jangan pernah menyimpan sandi atau token di $changes.
 * Kalau penulisan log gagal, permintaan pengguna tetap dilanjutkan.
 */
final class AuditLog
{
    public const LOGIN_FAILED = 'auth.login_failed';

    public const LOGIN_SUCCEEDED = 'auth.login_succeeded';

    public const PASSWORD_CHANGED = 'auth.password_changed';

    public const PASSWORD_CHANGE_FAILED = 'auth.password_change_failed';

    /**
     * @param  array<string, mixed>  $changes  jangan pernah memuat sandi atau token
     */
    public static function record(string $action, array $changes = [], ?User $actor = null): void
    {
        try {
            ActivityLog::create([
                'activity_log_user_id' => $actor?->user_id,
                'activity_log_action' => $action,
                'activity_log_entity_type' => 'user',
                'activity_log_entity_id' => $actor?->user_id,
                'activity_log_changes' => $changes,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
