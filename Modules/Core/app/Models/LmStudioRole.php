<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class LmStudioRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'role_name',
        'role_prompt',
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (self $role): void {
            if (! isset($role->uuid)) {
                $role->uuid = (string) Str::uuid();
            }
        });
    }

    public function jobs()
    {
        return $this->hasMany(LmStudioJob::class);
    }
}
