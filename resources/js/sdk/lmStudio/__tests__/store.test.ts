import { describe, expect, it, vi, beforeEach } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { useLmStudioStore } from '../store';
import { createMockStreamFactory } from '../stream';
import type {
    LmStudioInferenceJob,
    LmStudioModel,
    LmStudioStreamFactory,
} from '../types';

vi.mock('../client', () => ({
    listModels: vi.fn(),
    startInference: vi.fn(),
}));

const { listModels, startInference } = await import('../client');
const mockedListModels = vi.mocked(listModels);
const mockedStartInference = vi.mocked(startInference);

describe('useLmStudioStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('fetches models and updates state', async () => {
        const mockModels: LmStudioModel[] = [
            { id: 'mistral', owned_by: 'lmstudio', status: 'ready' },
        ];

        mockedListModels.mockResolvedValue(mockModels);

        const store = useLmStudioStore();

        await store.fetchModels();

        expect(store.models).toHaveLength(1);
        expect(store.models[0]?.id).toBe('mistral');
        expect(store.modelsError).toBeNull();
    });

    it('starts inference and streams chunks', async () => {
        const job: LmStudioInferenceJob = {
            job_id: 'job-1',
            model: 'mistral',
            created: Date.now(),
        };

        mockedStartInference.mockResolvedValue(job);

        const store = useLmStudioStore();
        const streamFactory = createMockStreamFactory();

        await store.startChat(
            {
                model: 'mistral',
                messages: [
                    { role: 'system', content: 'You are kind.' },
                    { role: 'user', content: 'Hello' },
                ],
            },
            { streamFactory }
        );

        expect(store.currentJob?.job_id).toBe('job-1');

        await vi.waitUntil(() => store.isStreaming === false, { timeout: 4000 });

        expect(store.chunks.length).toBeGreaterThan(0);
    });

    it('records stream errors', async () => {
        mockedStartInference.mockRejectedValue(new Error('offline'));

        const store = useLmStudioStore();

        await expect(
            store.startChat({
                model: 'mistral',
                messages: [{ role: 'user', content: 'hi' }],
            })
        ).rejects.toThrow();

        expect(store.streamError).toBe('offline');
    });

    it('handles retry and stop actions', async () => {
        const job: LmStudioInferenceJob = {
            job_id: 'job-retry',
            model: 'mistral',
            created: Date.now(),
        };

        mockedStartInference.mockResolvedValue(job);

        const store = useLmStudioStore();
        const immediateFactory = (): LmStudioStreamFactory => {
            return (_jobId, callbacks) => {
                callbacks.onChunk('chunk');
                callbacks.onCompleted({});

                return () => callbacks.onError(new Error('stopped'));
            };
        };

        await store.startChat(
            {
                model: 'mistral',
                messages: [{ role: 'user', content: 'hi' }],
            },
            { streamFactory: immediateFactory() }
        );

        expect(store.currentJob?.job_id).toBe('job-retry');
        store.retryStream(immediateFactory());
        await Promise.resolve();
        expect(store.retries).toBe(1);

        store.stopStream();
        expect(store.isStreaming).toBe(false);
    });
});
