<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Tujuh role dari kontrak bagian 1.3. Dosen PA dan pembimbing MBKM tetap disimpan
     * sebagai role karena ikut dikirim di klaim roles pada token.
     *
     * @var array<string, string>
     */
    public const ROLES = [
        'STUDENT' => 'Mahasiswa',
        'LECTURER' => 'Dosen',
        'ACADEMIC_ADVISOR' => 'Dosen Pembimbing Akademik',
        'MBKM_ADVISOR' => 'Dosen Pembimbing MBKM',
        'ADMIN_ACADEMIC' => 'Admin Akademik',
        'KAPRODI' => 'Ketua Program Studi',
        'BAAK' => 'Biro Administrasi Akademik dan Kemahasiswaan',
    ];

    public function run(): void
    {
        foreach (self::ROLES as $code => $name) {
            Role::updateOrCreate(
                ['role_code' => $code],
                ['role_name' => $name],
            );
        }
    }
}
