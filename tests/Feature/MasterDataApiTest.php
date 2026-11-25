<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\Lecturer;
use App\Models\Student;
use Database\Seeders\DemoIdentitySeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MasterDataApiTest extends TestCase
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

    private function token(string $identifier): string
    {
        return $this->postJson('/api/v1/auth/login', [
            'username' => $identifier,
            'password' => self::PASSWORD,
        ])->json('data.accessToken');
    }

    private function getAs(string $identifier, string $uri): TestResponse
    {
        return $this->withHeader('Authorization', 'Bearer '.$this->token($identifier))->getJson($uri);
    }

    public function test_mahasiswa_bisa_membaca_profilnya_sendiri(): void
    {
        $student = Student::where('student_nim', '1153000001')->firstOrFail();

        $response = $this->getAs('1153000001', '/api/v1/students/'.$student->student_id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Student profile retrieved successfully')
            ->assertJsonPath('meta', null)
            ->assertJsonPath('data.studentId', $student->student_id)
            ->assertJsonPath('data.nim', '1153000001')
            ->assertJsonPath('data.fullName', 'Mahasiswa Demo Satu')
            ->assertJsonPath('data.studyProgramName', 'Teknik Informatika')
            ->assertJsonPath('data.academicAdvisorName', 'Dosen Demo Satu, S.Kom., M.T.')
            ->assertJsonPath('data.academicStatus', 'ACTIVE')
            ->assertJsonPath('data.entryYear', 2022);

        $this->assertSame(
            ['studentId', 'nim', 'fullName', 'studyProgramId', 'studyProgramName', 'academicAdvisorId', 'academicAdvisorName', 'academicStatus', 'entryYear'],
            array_keys($response->json('data')),
        );
    }

    public function test_mahasiswa_tidak_boleh_membaca_data_mahasiswa_lain(): void
    {
        $other = Student::where('student_nim', '1153000002')->firstOrFail();

        $this->getAs('1153000001', '/api/v1/students/'.$other->student_id)
            ->assertStatus(403)
            ->assertJsonPath('error.type', 'FORBIDDEN_ACCESS');
    }

    public function test_admin_bisa_membaca_mahasiswa_lain(): void
    {
        $student = Student::where('student_nim', '1153000002')->firstOrFail();

        $this->getAs('9000000001', '/api/v1/students/'.$student->student_id)
            ->assertOk()
            ->assertJsonPath('data.nim', '1153000002');
    }

    public function test_mahasiswa_tidak_ditemukan_404_pesan_kontrak(): void
    {
        $this->getAs('9000000001', '/api/v1/students/std_tidak_ada')
            ->assertStatus(404)
            ->assertJsonPath('message', 'Student profile not found')
            ->assertJsonPath('error.type', 'RESOURCE_NOT_FOUND')
            ->assertJsonPath('error.details.0.field', 'studentId')
            ->assertJsonPath('error.details.0.message', 'Data mahasiswa dengan ID tersebut tidak ditemukan.');
    }

    public function test_profil_dosen_bisa_dibaca_semua_role(): void
    {
        $lecturer = Lecturer::firstOrFail();

        $response = $this->getAs('1153000001', '/api/v1/lecturers/'.$lecturer->lecturer_id);

        $response->assertOk()
            ->assertJsonPath('message', 'Lecturer profile retrieved successfully')
            ->assertJsonPath('data.nidn', '0415088501')
            ->assertJsonPath('data.status', 'ACTIVE');

        $this->assertSame(
            ['lecturerId', 'nidn', 'fullName', 'studyProgramId', 'studyProgramName', 'status'],
            array_keys($response->json('data')),
        );
    }

    public function test_dosen_tidak_ditemukan_404(): void
    {
        $this->getAs('1153000001', '/api/v1/lecturers/lec_tidak_ada')
            ->assertStatus(404)
            ->assertJsonPath('message', 'Lecturer profile not found')
            ->assertJsonPath('error.type', 'RESOURCE_NOT_FOUND');
    }

    public function test_periode_aktif_memakai_format_tanggal_iso_utc(): void
    {
        $response = $this->getAs('1153000001', '/api/v1/academic-periods/active');

        $response->assertOk()
            ->assertJsonPath('message', 'Active academic period retrieved')
            ->assertJsonPath('data.academicPeriodId', 'prd_20242')
            ->assertJsonPath('data.academicYear', '2024/2025')
            ->assertJsonPath('data.semesterType', 'EVEN')
            ->assertJsonPath('data.startDate', '2025-02-01T00:00:00Z')
            ->assertJsonPath('data.endDate', '2025-06-30T23:59:59Z')
            ->assertJsonPath('data.isKrsPhase', true)
            ->assertJsonPath('data.krsRegularDeadline', '2025-02-14T23:59:59Z')
            ->assertJsonPath('data.krsMbkmDeadline', '2025-02-20T23:59:59Z');

        $this->assertSame(
            ['academicPeriodId', 'academicYear', 'semesterType', 'startDate', 'endDate', 'isKrsPhase', 'krsRegularDeadline', 'krsMbkmDeadline'],
            array_keys($response->json('data')),
        );
    }

    public function test_tanpa_periode_aktif_404_pesan_kontrak(): void
    {
        AcademicPeriod::query()->update(['academic_period_is_active' => false]);

        $this->getAs('1153000001', '/api/v1/academic-periods/active')
            ->assertStatus(404)
            ->assertJsonPath('message', 'No active academic period configured')
            ->assertJsonPath('error.type', 'RESOURCE_NOT_FOUND');
    }

    public function test_semua_endpoint_master_data_menolak_tanpa_token(): void
    {
        $student = Student::firstOrFail();
        $lecturer = Lecturer::firstOrFail();

        foreach ([
            '/api/v1/students/'.$student->student_id,
            '/api/v1/lecturers/'.$lecturer->lecturer_id,
            '/api/v1/academic-periods/active',
        ] as $uri) {
            $this->getJson($uri)
                ->assertStatus(401)
                ->assertJsonPath('error.type', 'UNAUTHORIZED_TOKEN');
        }
    }
}
