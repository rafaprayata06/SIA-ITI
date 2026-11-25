<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tujuh role dari kontrak API bagian 1.3. Kode role ditulis kapital (STUDENT, dst).
        Schema::create('roles', function (Blueprint $table) {
            $table->increments('role_id');
            $table->string('role_code', 32)->unique();
            $table->string('role_name', 60);
            $table->timestamp('role_created_at')->nullable();
            $table->timestamp('role_updated_at')->nullable();
        });

        // Tabel penghubung: satu pengguna boleh punya beberapa role sekaligus.
        Schema::create('role_user', function (Blueprint $table) {
            $table->string('role_user_user_id', 40);
            $table->unsignedInteger('role_user_role_id');
            $table->primary(['role_user_user_id', 'role_user_role_id']);
            $table->foreign('role_user_user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->foreign('role_user_role_id')->references('role_id')->on('roles')->cascadeOnDelete();
        });

        Schema::create('study_programs', function (Blueprint $table) {
            $table->string('study_program_id', 40)->primary();
            $table->string('study_program_code', 10)->unique();
            $table->string('study_program_name', 120);
            $table->string('study_program_degree', 5);
            $table->string('study_program_head_lecturer_id', 40)->nullable()->index();
            $table->enum('study_program_status', ['aktif', 'non_aktif'])->default('aktif');
            $table->timestamp('study_program_created_at')->nullable();
            $table->timestamp('study_program_updated_at')->nullable();
        });

        // Dosen: NIDN unik, homebase program studi, status aktif/non_aktif.
        Schema::create('lecturers', function (Blueprint $table) {
            $table->string('lecturer_id', 40)->primary();
            $table->string('lecturer_user_id', 40)->nullable()->unique();
            $table->string('lecturer_nidn', 20)->unique();
            $table->string('lecturer_name', 120);
            $table->string('lecturer_study_program_id', 40);
            $table->enum('lecturer_status', ['aktif', 'non_aktif'])->default('aktif');
            $table->timestamp('lecturer_created_at')->nullable();
            $table->timestamp('lecturer_updated_at')->nullable();

            $table->foreign('lecturer_user_id')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('lecturer_study_program_id')->references('study_program_id')->on('study_programs');
        });

        // Status keaktifan tiga nilai (aktif/cuti/non_aktif) mengikuti BR-001, bukan boolean.
        Schema::create('students', function (Blueprint $table) {
            $table->string('student_id', 40)->primary();
            $table->string('student_user_id', 40)->nullable()->unique();
            $table->string('student_nim', 20)->unique();
            $table->string('student_name', 120);
            $table->string('student_study_program_id', 40);
            $table->string('student_advisor_lecturer_id', 40)->nullable();
            $table->smallInteger('student_entry_year');
            $table->enum('student_academic_status', ['aktif', 'cuti', 'non_aktif'])->default('aktif');
            $table->timestamp('student_created_at')->nullable();
            $table->timestamp('student_updated_at')->nullable();

            $table->foreign('student_user_id')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('student_study_program_id')->references('study_program_id')->on('study_programs');
            $table->foreign('student_advisor_lecturer_id')->references('lecturer_id')->on('lecturers')->nullOnDelete();
        });

        // Lima kolom pertama dari SRS 8.1. Semester, is_krs_phase, dan dua deadline KRS
        // ditambahkan karena kontrak 6.1 meminta field itu. ID memakai kode periode
        // (contoh prd_20242), bukan UUID.
        Schema::create('academic_periods', function (Blueprint $table) {
            $table->string('academic_period_id', 20)->primary();
            $table->string('academic_period_academic_year', 9);
            $table->enum('academic_period_term', ['ganjil', 'genap']);
            $table->date('academic_period_start_date');
            $table->date('academic_period_end_date');
            $table->boolean('academic_period_is_active')->default(false);
            $table->boolean('academic_period_is_krs_phase')->default(false);
            $table->dateTime('academic_period_krs_regular_deadline')->nullable();
            $table->dateTime('academic_period_krs_mbkm_deadline')->nullable();
            $table->timestamp('academic_period_created_at')->nullable();
            $table->timestamp('academic_period_updated_at')->nullable();
        });

        // Jejak audit untuk BR-007 dan SEC-007: siapa melakukan apa dan kapan.
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->string('activity_log_id', 40)->primary();
            $table->string('activity_log_user_id', 40)->nullable();
            $table->string('activity_log_action', 60);
            $table->string('activity_log_entity_type', 60);
            $table->string('activity_log_entity_id', 40)->nullable();
            $table->json('activity_log_changes')->nullable();
            $table->timestamp('activity_log_created_at')->nullable();

            $table->foreign('activity_log_user_id')->references('user_id')->on('users')->nullOnDelete();
            $table->index(['activity_log_entity_type', 'activity_log_entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('academic_periods');
        Schema::dropIfExists('students');
        Schema::dropIfExists('lecturers');
        Schema::dropIfExists('study_programs');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};
