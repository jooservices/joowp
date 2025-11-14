<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatRole;

final class StoreLmStudioJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prompt_message' => ['required', 'string'],
            'role' => ['nullable', 'string', Rule::in(array_map(
                static fn (ChatRole $role): string => $role->value,
                ChatRole::cases()
            ))],
            'lm_studio_role_id' => ['nullable', 'integer', 'exists:lm_studio_roles,id'],
        ];
    }
}
