import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { listModels, startInference } from './client';
import { createStreamFactory } from './stream';
import type {
    LmStudioInferenceJob,
    LmStudioInferenceRequest,
    LmStudioModel,
    LmStudioModelFilter,
    LmStudioStreamFactory,
} from './types';

function formatError(error: unknown): string {
    if (error instanceof Error) {
        return error.message;
    }

    return 'Unexpected LM Studio error.';
}

export const useLmStudioStore = defineStore('lmstudio', () => {
    const models = ref<LmStudioModel[]>([]);
    const isLoadingModels = ref(false);
    const modelsError = ref<string | null>(null);

    const currentJob = ref<LmStudioInferenceJob | null>(null);
    const chunks = ref<string[]>([]);
    const isStreaming = ref(false);
    const streamError = ref<string | null>(null);
    const retries = ref(0);
    const lastCompletedAt = ref<number | null>(null);

    let unsubscribe: (() => void) | null = null;

    async function fetchModels(filter?: LmStudioModelFilter) {
        isLoadingModels.value = true;
        modelsError.value = null;

        try {
            models.value = await listModels(filter);
        } catch (error) {
            modelsError.value = formatError(error);
            throw error;
        } finally {
            isLoadingModels.value = false;
        }
    }

    async function startChat(
        payload: LmStudioInferenceRequest,
        options?: { streamFactory?: LmStudioStreamFactory }
    ) {
        streamError.value = null;
        chunks.value = [];
        retries.value = 0;
        lastCompletedAt.value = null;
        isStreaming.value = true;

        try {
            const job = await startInference(payload);
            currentJob.value = job;
            subscribe(job.job_id, options?.streamFactory);
        } catch (error) {
            streamError.value = formatError(error);
            isStreaming.value = false;
            throw error;
        }
    }

    function subscribe(jobId: string, customFactory?: LmStudioStreamFactory) {
        unsubscribe?.();

        const factory = customFactory ?? createStreamFactory();
        unsubscribe = factory(jobId, {
            onChunk: (chunk) => {
                chunks.value.push(chunk);
            },
            onCompleted: (payload) => {
                lastCompletedAt.value = Date.now();
                isStreaming.value = false;
                unsubscribe?.();
                unsubscribe = null;

                if (payload?.error) {
                    streamError.value = String(payload.error);
                }
            },
            onError: (error) => {
                streamError.value = error.message;
                isStreaming.value = false;
            },
        });
    }

    function retryStream(customFactory?: LmStudioStreamFactory) {
        if (!currentJob.value) {
            return;
        }

        retries.value += 1;
        streamError.value = null;
        isStreaming.value = true;
        subscribe(currentJob.value.job_id, customFactory);
    }

    function stopStream() {
        unsubscribe?.();
        unsubscribe = null;
        isStreaming.value = false;
    }

    const output = computed(() => chunks.value.join('\n'));
    const hasModels = computed(() => models.value.length > 0);

    return {
        models,
        isLoadingModels,
        modelsError,
        currentJob,
        chunks,
        output,
        isStreaming,
        streamError,
        retries,
        lastCompletedAt,
        hasModels,
        fetchModels,
        startChat,
        retryStream,
        stopStream,
    };
});
