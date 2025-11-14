<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatCompletionRequest;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatMessage;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatRole;

final class LmStudioInferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roles = array_map(
            static fn (ChatRole $role): string => $role->value,
            ChatRole::cases()
        );

        return [
            'model' => ['nullable', 'string', 'max:255'],
            'messages' => ['required', 'array', 'min:1'],
            'messages.*.role' => ['required', 'string', Rule::in($roles)],
            'messages.*.content' => ['required', 'string'],
            'temperature' => ['nullable', 'numeric', 'between:0,2'],
            'top_p' => ['nullable', 'numeric', 'between:0,1'],
            'max_tokens' => ['nullable', 'integer', 'min:1'],
            'presence_penalty' => ['nullable', 'numeric', 'between:-2,2'],
            'frequency_penalty' => ['nullable', 'numeric', 'between:-2,2'],
            'seed' => ['nullable', 'integer'],
            'stream' => ['sometimes', 'boolean'],
            'stop' => ['nullable', 'array'],
            'stop.*' => ['string'],
        ];
    }

    public function toDto(): ChatCompletionRequest
    {
        $validated = $this->validated();
        $model = $validated['model'] ?? config('lmstudio.default_model');

        if (! is_string($model) || $model === '') {
            throw ValidationException::withMessages([
                'model' => 'A model must be provided when no default LM Studio model is configured.',
            ]);
        }

        $messages = array_map(
            static function (array $message): ChatMessage {
                return new ChatMessage(
                    role: ChatRole::from($message['role']),
                    content: (string) $message['content']
                );
            },
            $validated['messages']
        );

        return new ChatCompletionRequest(
            model: $model,
            messages: $messages,
            temperature: $validated['temperature'] ?? null,
            topP: $validated['top_p'] ?? null,
            maxTokens: $validated['max_tokens'] ?? null,
            presencePenalty: $validated['presence_penalty'] ?? null,
            frequencyPenalty: $validated['frequency_penalty'] ?? null,
            seed: $validated['seed'] ?? null,
            stream: (bool) ($validated['stream'] ?? config('lmstudio.enable_streaming', true)),
            stop: $validated['stop'] ?? null,
        );
    }
}
