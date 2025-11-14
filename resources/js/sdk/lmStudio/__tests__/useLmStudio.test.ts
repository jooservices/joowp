import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { useLmStudio } from '../composables/useLmStudio';
import { createMockRequest } from '../mocks';

vi.mock('../client', () => ({
    listModels: vi.fn().mockResolvedValue([]),
    startInference: vi.fn().mockResolvedValue({
        job_id: 'job-test',
        model: 'mistral',
        created: Date.now(),
    }),
}));

describe('useLmStudio composable', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('exposes refs from the store', () => {
        const { models, isStreaming } = useLmStudio();

        expect(Array.isArray(models.value)).toBe(true);
        expect(isStreaming.value).toBe(false);
    });

    it('delegates to store actions', async () => {
        const composable = useLmStudio();

        await composable.fetchModels();
        await composable.startChat(createMockRequest());

        expect(composable.currentJob.value?.job_id).toBe('job-test');
    });
});
