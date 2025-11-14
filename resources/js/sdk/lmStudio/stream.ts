import type { LmStudioChunkEvent, LmStudioStreamCallbacks, LmStudioStreamFactory } from './types';

const CHANNEL_PREFIX = 'lmstudio.inference.';
const EVENT_NAME = '.LmStudioInferenceStreamed';

function normalizeEvent(data: unknown): LmStudioChunkEvent | null {
    if (typeof data === 'object' && data !== null && 'type' in data) {
        return data as LmStudioChunkEvent;
    }

    if (typeof data === 'string') {
        try {
            return JSON.parse(data) as LmStudioChunkEvent;
        } catch {
            return null;
        }
    }

    return null;
}

function handleEvent(event: LmStudioChunkEvent, callbacks: LmStudioStreamCallbacks): void {
    if (event.type === 'chunk') {
        const content = (event.payload?.content as string | undefined) ?? '';
        callbacks.onChunk(content);

        return;
    }

    if (event.type === 'completed') {
        callbacks.onCompleted(event.payload ?? {});

        return;
    }

    callbacks.onError(new Error('LM Studio stream reported an error.'));
}

function createEchoStream(): LmStudioStreamFactory | null {
    const echo = (window as unknown as { Echo?: { channel(name: string): any } })?.Echo;

    if (!echo) {
        return null;
    }

    return (jobId, callbacks) => {
        const channel = echo.channel(`${CHANNEL_PREFIX}${jobId}`);
        const listener = (payload: unknown) => {
            const evt = normalizeEvent(payload);
            if (evt) {
                handleEvent(evt, callbacks);
            }
        };

        channel.listen(EVENT_NAME, listener);

        return () => {
            channel.stopListening(EVENT_NAME, listener);
        };
    };
}

function createEventSourceStream(): LmStudioStreamFactory | null {
    if (typeof EventSource === 'undefined') {
        return null;
    }

    return (jobId, callbacks) => {
        const source = new EventSource(`/api/v1/ai/lmstudio/stream/${jobId}`);

        source.onmessage = (message) => {
            const evt = normalizeEvent(message.data);
            if (evt) {
                handleEvent(evt, callbacks);
            }
        };

        source.onerror = () => {
            callbacks.onError(new Error('Failed to read LM Studio SSE stream.'));
            source.close();
        };

        return () => source.close();
    };
}

function createMockStream(): LmStudioStreamFactory {
    const demoChunks = [
        'Starting inference...',
        'Loading TensorRT weights...',
        'Simulating streamed response chunk 1.',
        'Simulating streamed response chunk 2.',
        'Simulating streamed response chunk 3.',
    ];

    return (jobId, callbacks) => {
        let index = 0;
        const interval = window.setInterval(() => {
            if (index < demoChunks.length) {
                const chunk = demoChunks[index] ?? '';
                callbacks.onChunk(chunk);
                index += 1;

                return;
            }

            callbacks.onCompleted({
                job_id: jobId,
                finished_at: Date.now(),
            });
            window.clearInterval(interval);
        }, 450);

        return () => {
            window.clearInterval(interval);
        };
    };
}

export function createStreamFactory(): LmStudioStreamFactory {
    const echo = createEchoStream();
    if (echo) {
        return echo;
    }

    const sse = createEventSourceStream();
    if (sse) {
        return sse;
    }

    return createMockStream();
}

export function createMockStreamFactory(): LmStudioStreamFactory {
    return createMockStream();
}
