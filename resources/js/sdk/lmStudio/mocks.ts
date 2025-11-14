import type {
    LmStudioInferenceJob,
    LmStudioInferenceRequest,
    LmStudioMessage,
    LmStudioModel,
} from './types';

export const mockModels: LmStudioModel[] = [
    {
        id: 'mistral-7b-instruct',
        owned_by: 'lmstudio',
        status: 'ready',
        created: 1731460800,
        metadata: {
            family: 'mistral',
            quantization_level: 'Q4_K_M',
        },
    },
    {
        id: 'llama-3-8b',
        owned_by: 'meta',
        status: 'ready',
        created: 1731460900,
        metadata: {
            family: 'llama',
            context_length: 8192,
        },
    },
];

const fallbackModelId = mockModels[0]?.id ?? 'local-model';

export function createMockMessage(content: string, role: LmStudioMessage['role'] = 'user') {
    return { role, content };
}

export function createMockRequest(): LmStudioInferenceRequest {
    return {
        model: fallbackModelId,
        messages: [
            createMockMessage('You are a helpful assistant.', 'system'),
            createMockMessage('Can you explain LM Studio streaming?'),
        ],
        temperature: 0.2,
        stream: true,
    };
}

export function createMockJob(): LmStudioInferenceJob {
    return {
        job_id: 'job-123',
        model: fallbackModelId,
        created: Date.now(),
    };
}
