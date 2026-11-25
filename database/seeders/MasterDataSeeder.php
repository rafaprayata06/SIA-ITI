<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\Lecturer;
use App\Models\StudyProgram;
use Illuminate\Database\Seeder;

/**
 * Data contoh supaya tiga endpoint master data ada isinya. Semua nama fiktif,
 * bukan data orang sungguhan (SRS bagian 8.2).
 */
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            'TI' => ['Teknik Informatika', 'S1'],
            'TM' => ['Teknik Mesin', 'S1'],
            'TS' => ['Teknik Sipil', 'S1'],
        ];

        foreach ($programs as $code => [$name, $degree]) {
            StudyProgram::updateOrCreate(
                ['study_program_code' => $code],
                [
                    'study_program_name' => $name,
                    'study_program_degree' => $degree,
                    'study_program_status' => 'aktif',
                ],
            );
        }

        $informatika = StudyProgram::where('study_program_code', 'TI')->firstOrFail();

        $lecturer = Lecturer::updateOrCreate(
            ['lecturer_nidn' => '0415088501'],
            [
                'lecturer_name' => 'Dosen Demo Satu, S.Kom., M.T.',
                'lecturer_study_program_id' => $informatika->study_program_id,
                'lecturer_status' => 'aktif',
            ],
        );

        $informatika->update(['study_program_head_lecturer_id' => $lecturer->lecturer_id]);

        AcademicPeriod::updateOrCreate(
            ['academic_period_id' => 'prd_20241'],
            [
                'academic_period_academic_year' => '2024/2025',
                'academic_period_term' => 'ganjil',
                'academic_period_start_date' => '2024-09-01',
                'academic_period_end_date' => '2025-01-31',
                'academic_period_is_active' => false,
                'academic_period_is_krs_phase' => false,
                'academic_period_krs_regular_deadline' => '2024-09-14 23:59:59',
                'academic_period_krs_mbkm_deadline' => '2024-09-20 23:59:59',
            ],
        );

        AcademicPeriod::updateOrCreate(
            ['academic_period_id' => 'prd_20242'],
            [
                'academic_period_academic_year' => '2024/2025',
                'academic_period_term' => 'genap',
                'academic_period_start_date' => '2025-02-01',
                'academic_period_end_date' => '2025-06-30',
                'academic_period_is_active' => true,
                'academic_period_is_krs_phase' => true,
                'academic_period_krs_regular_deadline' => '2025-02-14 23:59:59',
                'academic_period_krs_mbkm_deadline' => '2025-02-20 23:59:59',
            ],
        );
    }
}
