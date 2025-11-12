<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @phpstan-type StoreCategoryPayload array{
 *     name: string,
 *     slug?: string|null,
 *     description?: string|null,
 *     parent?: int|null
 * }
 */
final class StoreCategoryRequest extends FormRequest
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
            'parent' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return StoreCategoryPayload
     */
    public function validated($key = null, $default = null): array
    {
        /** @var StoreCategoryPayload $validated */
        $validated = parent::validated($key, $default);

        return $validated;
    }
}
