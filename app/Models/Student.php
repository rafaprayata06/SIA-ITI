<?php

namespace App\Models;

use App\Models\Concerns\HasPrefixedId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_user_id',
    'student_nim',
    'student_name',
    'student_study_program_id',
    'student_advisor_lecturer_id',
    'student_entry_year',
    'student_academic_status',
])]
class Student extends Model
{
    use HasPrefixedId;

    public const CREATED_AT = 'student_created_at';

    public const UPDATED_AT = 'student_updated_at';

    /**
     * Di database disimpan aktif/cuti/non_aktif (istilah SRS). Respons API wajib
     * ACTIVE/CUTI/NON_ACTIVE, jadi konversinya lewat daftar ini.
     *
     * @var array<string, string>
     */
    public const ACADEMIC_STATUS_TO_CONTRACT = [
        'aktif' => 'ACTIVE',
        'cuti' => 'CUTI',
        'non_aktif' => 'NON_ACTIVE',
    ];

    protected $table = 'students';

    protected $primaryKey = 'student_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public function idPrefix(): string
    {
        return 'std';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'student_entry_year' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id', 'user_id');
    }

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class, 'student_study_program_id', 'study_program_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'student_advisor_lecturer_id', 'lecturer_id');
    }

    protected function academicStatusContract(): Attribute
    {
        return Attribute::get(
            fn (): ?string => self::ACADEMIC_STATUS_TO_CONTRACT[$this->student_academic_status] ?? null
        );
    }
}
