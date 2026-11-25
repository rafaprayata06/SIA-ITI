<?php

namespace App\Models;

use App\Models\Concerns\HasPrefixedId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'study_program_code',
    'study_program_name',
    'study_program_degree',
    'study_program_head_lecturer_id',
    'study_program_status',
])]
class StudyProgram extends Model
{
    use HasPrefixedId;

    public const CREATED_AT = 'study_program_created_at';

    public const UPDATED_AT = 'study_program_updated_at';

    protected $table = 'study_programs';

    protected $primaryKey = 'study_program_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public function idPrefix(): string
    {
        return 'sp';
    }

    public function headLecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'study_program_head_lecturer_id', 'lecturer_id');
    }

    public function lecturers(): HasMany
    {
        return $this->hasMany(Lecturer::class, 'lecturer_study_program_id', 'study_program_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'student_study_program_id', 'study_program_id');
    }
}
