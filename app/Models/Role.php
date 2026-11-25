<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['role_code', 'role_name'])]
class Role extends Model
{
    public const CREATED_AT = 'role_created_at';

    public const UPDATED_AT = 'role_updated_at';

    protected $table = 'roles';

    protected $primaryKey = 'role_id';

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user', 'role_user_role_id', 'role_user_user_id');
    }
}
