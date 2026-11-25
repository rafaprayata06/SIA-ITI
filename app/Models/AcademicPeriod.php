<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'academic_period_id',
    'academic_period_academic_year',
    'academic_period_term',
    'academic_period_start_date',
    'academic_period_end_date',
    'academic_period_is_active',
    'academic_period_is_krs_phase',
    'academic_period_krs_regular_deadline',
    'academic_period_krs_mbkm_deadline',
])]
class AcademicPeriod extends Model
{
    public const CREATED_AT = 'academic_period_created_at';

    public const UPDATED_AT = 'academic_period_updated_at';

    /**
     * Di database ganjil/genap, di respons API ODD/EVEN.
     *
     * @var array<string, string>
     */
    public const TERM_TO_CONTRACT = [
        'ganjil' => 'ODD',
        'genap' => 'EVEN',
    ];

    protected $table = 'academic_periods';

    protected $primaryKey = 'academic_period_id';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'academic_period_start_date' => 'date',
            'academic_period_end_date' => 'date',
            'academic_period_is_active' => 'boolean',
            'academic_period_is_krs_phase' => 'boolean',
            'academic_period_krs_regular_deadline' => 'datetime',
            'academic_period_krs_mbkm_deadline' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('academic_period_is_active', true);
    }
}
