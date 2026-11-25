<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Lecturer;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * FR-003. GET /lecturers/{lecturerId}. Semua role boleh membaca selama token sah (kontrak 6.1).
 */
class LecturerController extends Controller
{
    public function show(string $lecturerId): JsonResponse
    {
        $lecturer = Lecturer::with('studyProgram')->find($lecturerId);

        if ($lecturer === null) {
            return ApiResponse::error(404, ApiResponse::RESOURCE_NOT_FOUND, 'Lecturer profile not found');
        }

        return ApiResponse::success([
            'lecturerId' => $lecturer->lecturer_id,
            'nidn' => $lecturer->lecturer_nidn,
            'fullName' => $lecturer->lecturer_name,
            'studyProgramId' => $lecturer->lecturer_study_program_id,
            'studyProgramName' => $lecturer->studyProgram?->study_program_name,
            'status' => $lecturer->status_contract,
        ], 'Lecturer profile retrieved successfully');
    }
}
