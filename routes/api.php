<?php

use App\Http\Controllers\Api\V1\AcademicPeriodController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\LecturerController;
use App\Http\Controllers\Api\V1\StudentController;
use Illuminate\Support\Facades\Route;

// Endpoint milik Tim 1 (auth dan master data). Tim lain sebaiknya menaruh rutenya
// di berkas sendiri lalu memuatnya dari sini, supaya berkas ini tidak bentrok.
// SEC-INT-003: 100 request/menit per IP.
Route::prefix('v1')->middleware('throttle:100,1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('api.auth')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

        Route::middleware('role:ADMIN_ACADEMIC,LECTURER,ACADEMIC_ADVISOR,STUDENT')
            ->get('/students/{studentId}', [StudentController::class, 'show']);

        Route::get('/lecturers/{lecturerId}', [LecturerController::class, 'show']);
        Route::get('/academic-periods/active', [AcademicPeriodController::class, 'active']);
    });
});
