import { storeToRefs } from 'pinia';
import { useLmStudioStore } from '../store';
import type {
    LmStudioInferenceRequest,
    LmStudioModelFilter,
    LmStudioStreamFactory,
} from '../types';

export function useLmStudio() {
    const store = useLmStudioStore();
    const refs = storeToRefs(store);

    function fetchModels(filter?: LmStudioModelFilter) {
        return store.fetchModels(filter);
    }

    function startChat(payload: LmStudioInferenceRequest, streamFactory?: LmStudioStreamFactory) {
        return store.startChat(payload, { streamFactory });
    }

    function retryStream(streamFactory?: LmStudioStreamFactory) {
        return store.retryStream(streamFactory);
    }

    return {
        ...refs,
        fetchModels,
        startChat,
        retryStream,
        stopStream: store.stopStream,
    };
}
