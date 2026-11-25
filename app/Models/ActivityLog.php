<?php

namespace App\Models;

use App\Models\Concerns\HasPrefixedId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'activity_log_user_id',
    'activity_log_action',
    'activity_log_entity_type',
    'activity_log_entity_id',
    'activity_log_changes',
])]
class ActivityLog extends Model
{
    use HasPrefixedId;

    public const CREATED_AT = 'activity_log_created_at';

    public const UPDATED_AT = null;

    protected $table = 'activity_logs';

    protected $primaryKey = 'activity_log_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public function idPrefix(): string
    {
        return 'log';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activity_log_changes' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activity_log_user_id', 'user_id');
    }
}
