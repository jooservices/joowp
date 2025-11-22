<?php

declare(strict_types=1);

namespace Modules\WordPress\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @phpstan-type StoreTagPayload array{
 *     name: string,
 *     slug?: string|null,
 *     description?: string|null
 * }
 */
final class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @param  string|null  $key
     * @param  mixed  $default
     * @return StoreTagPayload
     */
    public function validated($key = null, $default = null): array
    {
        /** @var StoreTagPayload $validated */
        $validated = parent::validated($key, $default);

        return $validated;
    }
}
