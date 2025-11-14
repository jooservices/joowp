<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { useLmStudio } from '@/sdk/lmStudio';
import { mockModels } from '@/sdk/lmStudio/mocks';
import { createMockStreamFactory } from '@/sdk/lmStudio/stream';
import type { LmStudioInferenceRequest } from '@/sdk/lmStudio';

const store = useLmStudio();
const useMockStream = ref(true);
const request = reactive({
  model: mockModels[0]?.id ?? '',
  systemPrompt: 'You are a helpful assistant.',
  userPrompt: 'Summarise the benefits of streaming LM Studio responses.',
  temperature: 0.2,
});

const canSubmit = computed(() => {
  return (
    request.userPrompt.trim().length > 0 &&
    !store.isStreaming.value &&
    (request.model || store.models.value[0]?.id)
  )
})

const isOffline = computed(() => Boolean(store.streamError.value))

function buildPayload(): LmStudioInferenceRequest {
  return {
    model: request.model || store.models.value[0]?.id,
    temperature: request.temperature,
    stream: true,
    messages: [
      { role: 'system', content: request.systemPrompt },
      { role: 'user', content: request.userPrompt },
    ],
  }
}

function currentStreamFactory() {
  return useMockStream.value ? createMockStreamFactory() : undefined
}

async function submit() {
  if (!canSubmit.value) return

  try {
    await store.startChat(buildPayload(), currentStreamFactory())
  } catch {
    // errors already captured in store
  }
}

function retryStream() {
  store.retryStream(currentStreamFactory())
}

onMounted(() => {
  store.fetchModels().catch(() => undefined)
})
</script>

<template>
  <div class="mx-auto max-w-4xl space-y-8 py-12">
    <header class="space-y-2">
      <p class="text-xs font-semibold uppercase tracking-widest text-indigo-500">LM Studio</p>
      <h1 class="text-3xl font-bold text-slate-900">Frontend Streaming Demo</h1>
      <p class="text-sm text-slate-600">
        Configure a prompt, start an inference, and observe streamed chunks. Toggle the mock stream
        to simulate offline usage.
      </p>
    </header>

    <section class="grid gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:grid-cols-2">
      <form class="space-y-4" @submit.prevent="submit">
        <label class="block text-sm font-medium text-slate-700">
          Model
          <select
            v-model="request.model"
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
          >
            <option
              v-for="model in store.models.value"
              :key="model.id"
              :value="model.id"
            >
              {{ model.id }} ({{ model.status }})
            </option>
          </select>
        </label>

        <label class="block text-sm font-medium text-slate-700">
          System prompt
          <textarea
            v-model="request.systemPrompt"
            rows="2"
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
          />
        </label>

        <label class="block text-sm font-medium text-slate-700">
          User prompt
          <textarea
            v-model="request.userPrompt"
            rows="4"
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
          />
        </label>

        <label class="block text-sm font-medium text-slate-700">
          Temperature
          <input
            v-model.number="request.temperature"
            type="number"
            step="0.1"
            min="0"
            max="2"
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
          />
        </label>

        <div class="flex items-center justify-between rounded-md bg-slate-50 p-3 text-xs text-slate-600">
          <label class="flex items-center gap-2">
            <input v-model="useMockStream" type="checkbox" class="rounded border-slate-300" />
            Use mock stream (offline)
          </label>
          <span class="font-mono text-[11px] uppercase text-indigo-500">
            {{ store.currentJob.value?.job_id ?? 'no job' }}
          </span>
        </div>

        <div class="flex gap-3">
          <button
            type="submit"
            :disabled="!canSubmit"
            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-40"
          >
            {{ store.isStreaming.value ? 'Streaming…' : 'Start inference' }}
          </button>
          <button
            type="button"
            class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700"
            @click="retryStream"
            :disabled="!store.streamError.value && !store.currentJob.value"
          >
            Retry stream
          </button>
        </div>

        <p v-if="store.modelsError.value" class="text-xs text-rose-600">
          Failed to fetch models: {{ store.modelsError.value }}
        </p>
        <p v-if="store.streamError.value" class="text-xs text-rose-600">
          Stream error: {{ store.streamError.value }}
        </p>
      </form>

      <div class="flex h-full flex-col rounded-xl border border-slate-100 bg-slate-900 text-slate-100">
        <header class="flex items-center justify-between border-b border-slate-800 px-4 py-3 text-xs uppercase tracking-widest">
          <span>Streaming Output</span>
          <span :class="isOffline ? 'text-rose-400' : 'text-emerald-400'">
            {{ isOffline ? 'offline' : store.isStreaming.value ? 'connected' : 'idle' }}
          </span>
        </header>

        <div class="flex-1 space-y-4 overflow-y-auto px-4 py-4 text-sm">
          <p
            v-for="(chunk, index) in store.chunks.value"
            :key="index"
            class="font-mono text-[13px] leading-relaxed text-emerald-100"
          >
            {{ chunk }}
          </p>
          <p v-if="!store.chunks.value.length" class="text-xs text-slate-400">
            Streamed text will appear here once an inference job starts.
          </p>
        </div>

        <footer class="border-t border-slate-800 px-4 py-3 text-xs text-slate-400">
          Retries: {{ store.retries.value }} • Last completed:
          {{ store.lastCompletedAt.value ? new Date(store.lastCompletedAt.value).toLocaleTimeString() : 'n/a' }}
        </footer>
      </div>
    </section>
  </div>
</template>
