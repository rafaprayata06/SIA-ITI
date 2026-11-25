<?php

namespace App\Models;

use App\Models\Concerns\HasPrefixedId;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['user_identifier', 'user_full_name', 'user_password', 'user_status'])]
#[Hidden(['user_password'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPrefixedId, Notifiable;

    public const CREATED_AT = 'user_created_at';

    public const UPDATED_AT = 'user_updated_at';

    protected $table = 'users';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public function idPrefix(): string
    {
        return 'usr';
    }

    public function getAuthPassword(): string
    {
        return $this->user_password;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_password' => 'hashed',
            'user_status' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'role_user_user_id', 'role_user_role_id');
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'student_user_id', 'user_id');
    }

    public function lecturer(): HasOne
    {
        return $this->hasOne(Lecturer::class, 'lecturer_user_id', 'user_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'activity_log_user_id', 'user_id');
    }
}
