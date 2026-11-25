<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Akun pengguna T1. Login memakai NIM/NIDN, jadi kolomnya user_identifier, bukan email.
        Schema::create('users', function (Blueprint $table) {
            $table->string('user_id', 40)->primary();
            $table->string('user_identifier', 32)->unique();
            $table->string('user_full_name', 120);
            $table->string('user_password');
            $table->boolean('user_status')->default(true);
            $table->timestamp('user_created_at')->nullable();
            $table->timestamp('user_updated_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id', 40)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
