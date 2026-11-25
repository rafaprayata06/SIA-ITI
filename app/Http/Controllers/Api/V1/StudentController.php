<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * FR-003. GET /students/{studentId} sesuai kontrak 6.1.
 */
class StudentController extends Controller
{
    // Role yang boleh membaca data mahasiswa lain. Mahasiswa hanya boleh membaca datanya sendiri.
    private const STAFF_ROLES = ['ADMIN_ACADEMIC', 'LECTURER', 'ACADEMIC_ADVISOR'];

    public function show(Request $request, string $studentId): JsonResponse
    {
        $student = Student::with(['studyProgram', 'advisor'])->find($studentId);

        if ($student === null) {
            return ApiResponse::error(
                404,
                ApiResponse::RESOURCE_NOT_FOUND,
                'Student profile not found',
                [['field' => 'studentId', 'message' => 'Data mahasiswa dengan ID tersebut tidak ditemukan.']],
            );
        }

        /** @var User $user */
        $user = $request->user();
        $isStaff = $user->roles->whereIn('role_code', self::STAFF_ROLES)->isNotEmpty();

        if (! $isStaff && $student->student_user_id !== $user->user_id) {
            return ApiResponse::error(
                403,
                ApiResponse::FORBIDDEN_ACCESS,
                'You are not allowed to access this resource',
            );
        }

        return ApiResponse::success([
            'studentId' => $student->student_id,
            'nim' => $student->student_nim,
            'fullName' => $student->student_name,
            'studyProgramId' => $student->student_study_program_id,
            'studyProgramName' => $student->studyProgram?->study_program_name,
            'academicAdvisorId' => $student->student_advisor_lecturer_id,
            'academicAdvisorName' => $student->advisor?->lecturer_name,
            'academicStatus' => $student->academic_status_contract,
            'entryYear' => $student->student_entry_year,
        ], 'Student profile retrieved successfully');
    }
}
