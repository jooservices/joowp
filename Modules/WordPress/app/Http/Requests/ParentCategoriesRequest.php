<?php

declare(strict_types=1);

namespace Modules\WordPress\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @phpstan-type ParentFilters array{
 *     exclude?: int|null,
 *     include_trashed?: bool
 * }
 */
final class ParentCategoriesRequest extends FormRequest
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
            'exclude' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'include_trashed' => ['sometimes', 'boolean', 'nullable'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string "true"/"false" to boolean for query parameters
        if ($this->has('include_trashed')) {
            $value = $this->input('include_trashed');
            if (is_string($value)) {
                $this->merge([
                    'include_trashed' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                ]);
            }
        }
    }

    /**
     * @param  string|null  $key
     * @param  mixed  $default
     * @return ParentFilters
     */
    public function validated($key = null, $default = null): array
    {
        /** @var ParentFilters $validated */
        $validated = parent::validated($key, $default);

        return $validated;
    }
}
