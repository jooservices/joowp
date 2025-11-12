<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @phpstan-type IndexFilters array{
 *     search?: string|null,
 *     per_page?: int,
 *     page?: int
 * }
 */
final class IndexCategoriesRequest extends FormRequest
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
            'search' => ['sometimes', 'nullable', 'string', 'max:120'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return IndexFilters
     */
    public function validated($key = null, $default = null): array
    {
        /** @var IndexFilters $validated */
        $validated = parent::validated($key, $default);

        return $validated;
    }
}
