<?php

namespace Database\Seeders;

use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun untuk mencoba login. Kata sandinya dibaca dari env DEMO_SEED_PASSWORD,
 * tidak ditulis di kode (SEC-004).
 */
class DemoIdentitySeeder extends Seeder
{
    public function run(?string $password = null): void
    {
        $password ??= env('DEMO_SEED_PASSWORD');

        if (blank($password)) {
            $this->command?->warn('DEMO_SEED_PASSWORD kosong — seeder akun demo dilewati.');

            return;
        }

        $informatika = StudyProgram::where('study_program_code', 'TI')->firstOrFail();
        $advisor = Lecturer::where('lecturer_nidn', '0415088501')->firstOrFail();

        $users = [
            ['9000000001', 'Admin Demo', ['ADMIN_ACADEMIC']],
            ['9000000002', 'BAAK Demo', ['BAAK']],
            ['0415088501', 'Dosen Demo Satu, S.Kom., M.T.', ['LECTURER', 'ACADEMIC_ADVISOR']],
            ['1153000001', 'Mahasiswa Demo Satu', ['STUDENT']],
            ['1153000002', 'Mahasiswa Demo Dua', ['STUDENT']],
        ];

        foreach ($users as [$identifier, $fullName, $roles]) {
            $user = User::updateOrCreate(
                ['user_identifier' => $identifier],
                ['user_full_name' => $fullName, 'user_password' => $password, 'user_status' => true],
            );

            $user->roles()->sync(
                Role::whereIn('role_code', $roles)->pluck('role_id')
            );
        }

        $advisor->update([
            'lecturer_user_id' => User::where('user_identifier', '0415088501')->value('user_id'),
        ]);

        foreach ([['1153000001', 'Mahasiswa Demo Satu'], ['1153000002', 'Mahasiswa Demo Dua']] as [$nim, $name]) {
            Student::updateOrCreate(
                ['student_nim' => $nim],
                [
                    'student_user_id' => User::where('user_identifier', $nim)->value('user_id'),
                    'student_name' => $name,
                    'student_study_program_id' => $informatika->study_program_id,
                    'student_advisor_lecturer_id' => $advisor->lecturer_id,
                    'student_entry_year' => 2022,
                    'student_academic_status' => 'aktif',
                ],
            );
        }
    }
}
