export type LmStudioRole = 'system' | 'user' | 'assistant' | 'tool';

export interface LmStudioModel {
    id: string;
    owned_by: string;
    status: string;
    created?: number | null;
    metadata?: Record<string, unknown>;
}

export interface LmStudioModelFilter {
    owned_by?: string;
    status?: string;
    limit?: number;
    cursor?: string;
}

export interface LmStudioMessage {
    role: LmStudioRole;
    content: string;
    name?: string;
}

export interface LmStudioInferenceRequest {
    model?: string;
    messages: LmStudioMessage[];
    temperature?: number;
    top_p?: number;
    max_tokens?: number;
    presence_penalty?: number;
    frequency_penalty?: number;
    seed?: number;
    stream?: boolean;
    stop?: string[];
}

export interface LmStudioInferenceJob {
    job_id: string;
    model: string;
    created: number;
}

export interface LmStudioChunkEvent {
    job_id: string;
    type: 'chunk' | 'completed' | 'error';
    payload?: Record<string, unknown>;
}

export interface ApiEnvelope<T> {
    ok: boolean;
    code: string;
    message: string;
    status: number;
    data?: T;
}

export interface LmStudioStreamCallbacks {
    onChunk(content: string): void;
    onCompleted(payload: Record<string, unknown>): void;
    onError(error: Error): void;
}

export type LmStudioStreamFactory = (
    jobId: string,
    callbacks: LmStudioStreamCallbacks
) => () => void;
