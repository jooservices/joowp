<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class LmStudioJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'prompt_message',
        'respond_message',
        'role',
        'started_at',
        'completed_at',
        'lm_studio_role_id',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (self $job): void {
            if (! isset($job->uuid)) {
                $job->uuid = (string) Str::uuid();
            }
        });
    }

    public function roleDefinition()
    {
        return $this->belongsTo(LmStudioRole::class, 'lm_studio_role_id');
    }
}
