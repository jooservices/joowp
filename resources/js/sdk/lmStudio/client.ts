import { httpClient } from '../../bootstrap';
import type {
    ApiEnvelope,
    LmStudioInferenceJob,
    LmStudioInferenceRequest,
    LmStudioModel,
    LmStudioModelFilter,
} from './types';

const BASE_URL = '/api/v1/ai/lmstudio';

function unwrap<T>(data: ApiEnvelope<T>): T {
    if (!data.ok) {
        const error = new Error(data.message ?? 'LM Studio request failed');
        throw error;
    }

    if (data.data === undefined) {
        throw new Error('Malformed LM Studio response');
    }

    return data.data;
}

export async function listModels(filter?: LmStudioModelFilter): Promise<LmStudioModel[]> {
    const response = await httpClient.get<ApiEnvelope<{ models: LmStudioModel[] }>>(
        `${BASE_URL}/models`,
        {
            params: filter,
        }
    );

    return unwrap(response.data).models ?? [];
}

export async function startInference(
    payload: LmStudioInferenceRequest
): Promise<LmStudioInferenceJob> {
    const response = await httpClient.post<ApiEnvelope<LmStudioInferenceJob>>(
        `${BASE_URL}/infer`,
        payload
    );

    return unwrap(response.data);
}
