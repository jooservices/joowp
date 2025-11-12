<?php

declare(strict_types=1);

namespace Modules\WordPress\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $username
 * @property string|null $token
 * @property array|null $payload
 *
 * @method static \Illuminate\Database\Eloquent\Builder<WpToken> query()
 * @method static WpToken updateOrCreate(array $attributes, array $values = [])
 */
final class WpToken extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<WpToken>> */
    use HasFactory;

    protected $table = 'wp_tokens';

    protected $fillable = [
        'username',
        'token',
        'payload',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
    ];
}
