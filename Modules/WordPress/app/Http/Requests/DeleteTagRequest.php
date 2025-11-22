<?php

declare(strict_types=1);

namespace Modules\WordPress\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @phpstan-type DeleteTagPayload array{
 *     force?: bool
 * }
 */
final class DeleteTagRequest extends FormRequest
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
            'force' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @param  string|null  $key
     * @param  mixed  $default
     * @return DeleteTagPayload
     */
    public function validated($key = null, $default = null): array
    {
        /** @var DeleteTagPayload $validated */
        $validated = parent::validated($key, $default);

        return $validated;
    }
}
