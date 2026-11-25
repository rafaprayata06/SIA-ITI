<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * FR-003. GET /academic-periods/active. Semua role boleh membaca selama token sah (kontrak 6.1).
 */
class AcademicPeriodController extends Controller
{
    private const ISO8601_UTC = 'Y-m-d\TH:i:s\Z';

    public function active(): JsonResponse
    {
        $period = AcademicPeriod::active()->first();

        if ($period === null) {
            return ApiResponse::error(404, ApiResponse::RESOURCE_NOT_FOUND, 'No active academic period configured');
        }

        return ApiResponse::success([
            'academicPeriodId' => $period->academic_period_id,
            'academicYear' => $period->academic_period_academic_year,
            'semesterType' => AcademicPeriod::TERM_TO_CONTRACT[$period->academic_period_term] ?? null,
            // Kolom tanggal di database tidak menyimpan jam. Kontrak meminta ISO 8601 UTC,
            // jadi tanggal mulai dikirim 00:00:00Z dan tanggal selesai 23:59:59Z.
            'startDate' => $period->academic_period_start_date->startOfDay()->format(self::ISO8601_UTC),
            'endDate' => $period->academic_period_end_date->endOfDay()->format(self::ISO8601_UTC),
            'isKrsPhase' => $period->academic_period_is_krs_phase,
            'krsRegularDeadline' => $period->academic_period_krs_regular_deadline?->format(self::ISO8601_UTC),
            'krsMbkmDeadline' => $period->academic_period_krs_mbkm_deadline?->format(self::ISO8601_UTC),
        ], 'Active academic period retrieved');
    }
}
