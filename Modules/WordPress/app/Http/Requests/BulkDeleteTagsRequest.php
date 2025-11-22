<?php

declare(strict_types=1);

namespace Modules\WordPress\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @phpstan-type BulkDeleteTagsPayload array{
 *     tag_ids: array<int>,
 *     force?: bool
 * }
 */
final class BulkDeleteTagsRequest extends FormRequest
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
            'tag_ids' => ['required', 'array', 'min:1', 'max:100'],
            'tag_ids.*' => ['required', 'integer', 'min:1'],
            'force' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @param  string|null  $key
     * @param  mixed  $default
     * @return BulkDeleteTagsPayload
     */
    public function validated($key = null, $default = null): array
    {
        /** @var BulkDeleteTagsPayload $validated */
        $validated = parent::validated($key, $default);

        return $validated;
    }
}

