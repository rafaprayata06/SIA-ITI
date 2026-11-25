<?php

namespace App\Models;

use App\Models\Concerns\HasPrefixedId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'lecturer_user_id',
    'lecturer_nidn',
    'lecturer_name',
    'lecturer_study_program_id',
    'lecturer_status',
])]
class Lecturer extends Model
{
    use HasPrefixedId;

    /**
     * Di database aktif/non_aktif, di respons API ACTIVE/INACTIVE.
     * Jangan pakai strtoupper(): hasilnya AKTIF, bukan ACTIVE.
     *
     * @var array<string, string>
     */
    public const STATUS_TO_CONTRACT = [
        'aktif' => 'ACTIVE',
        'non_aktif' => 'INACTIVE',
    ];

    public const CREATED_AT = 'lecturer_created_at';

    public const UPDATED_AT = 'lecturer_updated_at';

    protected $table = 'lecturers';

    protected $primaryKey = 'lecturer_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public function idPrefix(): string
    {
        return 'lec';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_user_id', 'user_id');
    }

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class, 'lecturer_study_program_id', 'study_program_id');
    }

    public function advisees(): HasMany
    {
        return $this->hasMany(Student::class, 'student_advisor_lecturer_id', 'lecturer_id');
    }

    protected function statusContract(): Attribute
    {
        return Attribute::get(
            fn (): ?string => self::STATUS_TO_CONTRACT[$this->lecturer_status] ?? null
        );
    }
}
