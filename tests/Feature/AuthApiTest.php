<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Jwt;
use Database\Seeders\DemoIdentitySeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AuthApiTest extends TestCase
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

    private function login(string $identifier, ?string $password = null): TestResponse
    {
        return $this->postJson('/api/v1/auth/login', [
            'username' => $identifier,
            'password' => $password ?? self::PASSWORD,
        ]);
    }

    public function test_login_mengembalikan_amplop_dan_klaim_token_kontrak(): void
    {
        $response = $this->login('1153000001');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 200)
            ->assertJsonPath('message', 'Authentication successful')
            ->assertJsonPath('meta', null)
            ->assertJsonPath('data.tokenType', 'Bearer')
            ->assertJsonPath('data.expiresIn', 43200)
            ->assertJsonPath('data.user.identifier', '1153000001')
            ->assertJsonPath('data.user.fullName', 'Mahasiswa Demo Satu')
            ->assertJsonPath('data.user.roles', ['STUDENT']);

        $this->assertStringStartsWith('usr_', $response->json('data.user.userId'));

        $claims = Jwt::decode($response->json('data.accessToken'));

        $this->assertIsArray($claims);
        $this->assertSame(Jwt::ISSUER, $claims['iss']);
        $this->assertSame('1153000001', $claims['identifier']);
        $this->assertSame(['STUDENT'], $claims['roles']);
        $this->assertSame(43200, $claims['exp'] - $claims['iat']);
        $this->assertStringStartsWith('jwt_', $claims['jti']);
    }

    public function test_login_kata_sandi_salah_401_tanpa_meta(): void
    {
        $response = $this->login('1153000001', 'salah-banget');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 401)
            ->assertJsonPath('message', 'Invalid username or password')
            ->assertJsonPath('error.type', 'AUTHENTICATION_FAILED')
            ->assertJsonPath('error.details.0.field', 'password');

        $this->assertArrayNotHasKey('meta', $response->json());
    }

    public function test_login_tanpa_kata_sandi_422_validation_error(): void
    {
        $this->postJson('/api/v1/auth/login', ['username' => '1153000001'])
            ->assertStatus(422)
            ->assertJsonPath('error.type', 'VALIDATION_ERROR')
            ->assertJsonPath('error.details.0.field', 'password')
            ->assertJsonPath('error.details.0.message', 'Kata sandi wajib diisi.');
    }

    public function test_logout_tanpa_token_401(): void
    {
        $this->postJson('/api/v1/auth/logout')
            ->assertStatus(401)
            ->assertJsonPath('error.type', 'UNAUTHORIZED_TOKEN')
            ->assertJsonPath('message', 'Token is missing or already invalidated');
    }

    public function test_token_kedaluwarsa_ditolak(): void
    {
        $user = User::where('user_identifier', '1153000001')->firstOrFail();

        $token = Jwt::encode([
            'iss' => Jwt::ISSUER,
            'sub' => $user->user_id,
            'identifier' => $user->user_identifier,
            'roles' => ['STUDENT'],
            'permissions' => [],
            'iat' => time() - 100,
            'exp' => time() - 10,
            'jti' => 'jwt_kedaluwarsa',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(401)
            ->assertJsonPath('error.type', 'UNAUTHORIZED_TOKEN');
    }

    public function test_logout_membatalkan_token(): void
    {
        $token = $this->login('1153000001')->json('data.accessToken');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Successfully logged out')
            ->assertJsonPath('data', null);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(401)
            ->assertJsonPath('error.type', 'UNAUTHORIZED_TOKEN');
    }

    public function test_ganti_kata_sandi_salah_422_pesan_kontrak(): void
    {
        $token = $this->login('1153000001')->json('data.accessToken');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/change-password', [
                'currentPassword' => 'salah',
                'newPassword' => 'SandiBaru123',
                'newPasswordConfirmation' => 'SandiBaru123',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Password validation failed')
            ->assertJsonPath('error.type', 'VALIDATION_ERROR')
            ->assertJsonPath('error.details.0.field', 'currentPassword')
            ->assertJsonPath('error.details.0.message', 'Kata sandi saat ini tidak sesuai.');
    }

    public function test_ganti_kata_sandi_berhasil_dan_sandi_baru_berlaku(): void
    {
        $token = $this->login('1153000001')->json('data.accessToken');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/change-password', [
                'currentPassword' => self::PASSWORD,
                'newPassword' => 'SandiBaru123',
                'newPasswordConfirmation' => 'SandiBaru123',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Password updated successfully')
            ->assertJsonPath('data', null);

        $this->login('1153000001', 'SandiBaru123')->assertOk();
        $this->login('1153000001', self::PASSWORD)->assertStatus(401);
    }

    public function test_middleware_role_menolak_403_dan_mengizinkan_role_berhak(): void
    {
        Route::middleware(['api.auth', 'role:ADMIN_ACADEMIC'])
            ->get('/api/v1/_probe', fn () => response()->json(['ok' => true]));

        $student = $this->login('1153000001')->json('data.accessToken');

        $this->withHeader('Authorization', 'Bearer '.$student)
            ->getJson('/api/v1/_probe')
            ->assertStatus(403)
            ->assertJsonPath('error.type', 'FORBIDDEN_ACCESS');

        $admin = $this->login('9000000001')->json('data.accessToken');

        $this->withHeader('Authorization', 'Bearer '.$admin)
            ->getJson('/api/v1/_probe')
            ->assertOk()
            ->assertJsonPath('ok', true);
    }
}
