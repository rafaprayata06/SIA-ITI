<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\AuditLog;
use Database\Seeders\DemoIdentitySeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'rahasia-tes-2026';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(MasterDataSeeder::class);
        (new DemoIdentitySeeder)->run(self::PASSWORD);
    }

    private function login(string $identifier, ?string $password = null)
    {
        return $this->postJson('/api/v1/auth/login', [
            'username' => $identifier,
            'password' => $password ?? self::PASSWORD,
        ]);
    }

    public function test_login_gagal_tercatat_tanpa_menyimpan_sandi(): void
    {
        $this->login('1153000001', 'sandi-salah-satu');
        $this->login('1153000001', 'sandi-salah-dua');
        $this->login('9999999999', 'sandi-salah-tiga');

        $user = User::where('user_identifier', '1153000001')->firstOrFail();
        $logs = ActivityLog::where('activity_log_action', AuditLog::LOGIN_FAILED)->get();

        $this->assertCount(3, $logs, 'setiap percobaan login gagal harus tercatat');

        $akunDikenal = $logs->filter(fn (ActivityLog $log) => $log->activity_log_changes['identifier'] === '1153000001');
        $akunAsing = $logs->filter(fn (ActivityLog $log) => $log->activity_log_changes['identifier'] === '9999999999');

        $this->assertCount(2, $akunDikenal);
        $this->assertSame($user->user_id, $akunDikenal->first()->activity_log_user_id);
        $this->assertCount(1, $akunAsing);
        $this->assertNull($akunAsing->first()->activity_log_user_id);
        $this->assertStringStartsWith('log_', $akunDikenal->first()->activity_log_id);

        foreach ($logs as $log) {
            $isi = json_encode($log->activity_log_changes);
            $this->assertStringNotContainsString('sandi-salah', $isi);
        }
    }

    public function test_login_berhasil_tercatat_dengan_aktor(): void
    {
        $this->login('1153000001');

        $user = User::where('user_identifier', '1153000001')->firstOrFail();
        $log = ActivityLog::where('activity_log_action', AuditLog::LOGIN_SUCCEEDED)->firstOrFail();

        $this->assertSame($user->user_id, $log->activity_log_user_id);
        $this->assertSame($user->user_id, $log->activity_log_entity_id);
        $this->assertSame(['STUDENT'], $log->activity_log_changes['roles']);
        $this->assertNotNull($log->activity_log_created_at);
    }

    public function test_ganti_sandi_berhasil_dan_gagal_tercatat(): void
    {
        $token = $this->login('1153000001')->json('data.accessToken');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/change-password', [
                'currentPassword' => 'salah',
                'newPassword' => 'SandiBaru123',
                'newPasswordConfirmation' => 'SandiBaru123',
            ])
            ->assertStatus(422);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/change-password', [
                'currentPassword' => self::PASSWORD,
                'newPassword' => 'SandiBaru123',
                'newPasswordConfirmation' => 'SandiBaru123',
            ])
            ->assertOk();

        $this->assertSame(1, ActivityLog::where('activity_log_action', AuditLog::PASSWORD_CHANGE_FAILED)->count());
        $this->assertSame(1, ActivityLog::where('activity_log_action', AuditLog::PASSWORD_CHANGED)->count());

        $changed = ActivityLog::where('activity_log_action', AuditLog::PASSWORD_CHANGED)->firstOrFail();
        $this->assertSame('user', $changed->activity_log_entity_type);
        $this->assertStringNotContainsString('SandiBaru123', json_encode($changed->activity_log_changes));
    }

    public function test_token_tidak_pernah_masuk_audit_log(): void
    {
        $token = $this->login('1153000001')->json('data.accessToken');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $semua = ActivityLog::all()->map(fn (ActivityLog $log) => json_encode($log->getAttributes()))->implode(' ');

        $this->assertStringNotContainsString($token, $semua);
        $this->assertStringNotContainsString('Bearer', $semua);
    }
}
