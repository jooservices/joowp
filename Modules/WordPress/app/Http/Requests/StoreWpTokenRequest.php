<?php

declare(strict_types=1);

namespace Modules\WordPress\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @phpstan-type StoreTokenPayload array{
 *     username: string,
 *     password: string,
 *     remember?: bool
 * }
 */
final class StoreWpTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @param  string|null  $key
     * @param  mixed  $default
     * @return StoreTokenPayload
     */
    public function validated($key = null, $default = null): array
    {
        /** @var StoreTokenPayload $validated */
        $validated = parent::validated($key, $default);

        return $validated;
    }
}
