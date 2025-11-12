<?php

declare(strict_types=1);

namespace Modules\WordPress\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @phpstan-type DeleteCategoryPayload array{
 *     force: bool
 * }
 */
final class DeleteCategoryRequest extends FormRequest
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
            'force' => ['required', 'boolean'],
        ];
    }

    /**
     * @param  string|null  $key
     * @param  mixed  $default
     * @return DeleteCategoryPayload
     */
    public function validated($key = null, $default = null): array
    {
        /** @var DeleteCategoryPayload $validated */
        $validated = parent::validated($key, $default);

        return $validated;
    }
}
