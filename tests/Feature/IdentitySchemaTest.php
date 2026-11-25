<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IdentitySchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabel_identitas_dan_master_data_terbentuk(): void
    {
        $tables = [
            'users', 'roles', 'role_user',
            'study_programs', 'lecturers', 'students',
            'academic_periods', 'activity_logs',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Tabel {$table} tidak terbentuk");
        }
    }

    public function test_id_pengguna_berprefiks_usr_dan_kata_sandi_di_hash(): void
    {
        $user = User::create([
            'user_identifier' => '1153000001',
            'user_full_name' => 'Mahasiswa Demo',
            'user_password' => 'rahasia123',
            'user_status' => true,
        ]);

        $this->assertStringStartsWith('usr_', $user->user_id);
        $this->assertSame(40, strlen($user->user_id));
        $this->assertNotSame('rahasia123', $user->fresh()->user_password);
        $this->assertTrue(Hash::check('rahasia123', $user->fresh()->user_password));
    }

    public function test_satu_pengguna_boleh_punya_banyak_role(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::create([
            'user_identifier' => '0415088501',
            'user_full_name' => 'Dosen Demo',
            'user_password' => 'rahasia123',
        ]);

        $user->roles()->sync(
            Role::whereIn('role_code', ['LECTURER', 'ACADEMIC_ADVISOR'])->pluck('role_id')
        );

        $codes = $user->fresh()->roles->pluck('role_code')->sort()->values()->all();

        $this->assertSame(['ACADEMIC_ADVISOR', 'LECTURER'], $codes);
        $this->assertCount(7, Role::all(), 'Role kontrak harus 7 baris');
    }

    public function test_mahasiswa_terhubung_prodi_dosen_pa_dan_status_dipetakan_ke_kontrak(): void
    {
        $this->seed(MasterDataSeeder::class);

        $program = \App\Models\StudyProgram::where('study_program_code', 'TI')->firstOrFail();
        $advisor = Lecturer::where('lecturer_nidn', '0415088501')->firstOrFail();

        $student = Student::create([
            'student_nim' => '1153000001',
            'student_name' => 'Mahasiswa Demo',
            'student_study_program_id' => $program->study_program_id,
            'student_advisor_lecturer_id' => $advisor->lecturer_id,
            'student_entry_year' => 2022,
            'student_academic_status' => 'aktif',
        ]);

        $this->assertStringStartsWith('std_', $student->student_id);
        $this->assertSame('Teknik Informatika', $student->studyProgram->study_program_name);
        $this->assertSame('0415088501', $student->advisor->lecturer_nidn);
        $this->assertSame('ACTIVE', $student->academic_status_contract);

        $student->update(['student_academic_status' => 'cuti']);
        $this->assertSame('CUTI', $student->fresh()->academic_status_contract);
    }

    public function test_periode_akademik_memakai_kode_bisnis_dan_scope_aktif(): void
    {
        $this->seed(MasterDataSeeder::class);

        $active = AcademicPeriod::active()->get();

        $this->assertCount(1, $active);
        $this->assertSame('prd_20242', $active->first()->academic_period_id);
        $this->assertSame('EVEN', AcademicPeriod::TERM_TO_CONTRACT[$active->first()->academic_period_term]);
        $this->assertTrue($active->first()->academic_period_is_krs_phase);
    }
}
