<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WpToken extends Model
{
    use HasFactory;

    protected $table = 'wp_tokens';

    protected $fillable = [
        'username',
        'token',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}

