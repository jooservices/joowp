<?php

declare(strict_types=1);

namespace Modules\Core\Services\LmStudio\Contracts;

use Modules\Core\Services\LmStudio\DTO\Audio\SpeechRequest;
use Modules\Core\Services\LmStudio\DTO\Audio\SpeechResponse;
use Modules\Core\Services\LmStudio\DTO\Audio\TranscriptionRequest;
use Modules\Core\Services\LmStudio\DTO\Audio\TranscriptionResponse;
use Modules\Core\Services\LmStudio\DTO\Audio\TranslationRequest;
use Modules\Core\Services\LmStudio\DTO\Audio\TranslationResponse;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatCompletionRequest;
use Modules\Core\Services\LmStudio\DTO\Chat\ChatCompletionResponse;
use Modules\Core\Services\LmStudio\DTO\Completion\CompletionRequest;
use Modules\Core\Services\LmStudio\DTO\Completion\CompletionResponse;
use Modules\Core\Services\LmStudio\DTO\Embedding\EmbeddingRequest;
use Modules\Core\Services\LmStudio\DTO\Embedding\EmbeddingResponse;
use Modules\Core\Services\LmStudio\DTO\HealthStatus;
use Modules\Core\Services\LmStudio\DTO\Image\ImageGenerationRequest;
use Modules\Core\Services\LmStudio\DTO\Image\ImageGenerationResponse;
use Modules\Core\Services\LmStudio\DTO\ListModelsFilter;
use Modules\Core\Services\LmStudio\DTO\ModelSummary;
use Modules\Core\Services\LmStudio\Streaming\StreamObserver;

interface SdkContract
{
    /**
     * @return array<int, ModelSummary>
     */
    public function listModels(?ListModelsFilter $filter = null): array;

    public function healthCheck(): HealthStatus;

    public function createChatCompletion(
        ChatCompletionRequest $request,
        ?StreamObserver $observer = null
    ): ChatCompletionResponse;

    public function createCompletion(
        CompletionRequest $request,
        ?StreamObserver $observer = null
    ): CompletionResponse;

    public function createEmbedding(EmbeddingRequest $request): EmbeddingResponse;

    public function createTranscription(TranscriptionRequest $request): TranscriptionResponse;

    public function createTranslation(TranslationRequest $request): TranslationResponse;

    public function createSpeech(SpeechRequest $request): SpeechResponse;

    public function createImage(ImageGenerationRequest $request): ImageGenerationResponse;
}
