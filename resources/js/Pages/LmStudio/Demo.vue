<script setup lang="ts">
const endpoints = [
  {
    method: 'GET',
    path: '/api/v1/ai/lmstudio/models',
    description: 'Lists models discovered on the local LM Studio instance.',
  },
  {
    method: 'POST',
    path: '/api/v1/ai/lmstudio/infer',
    description:
      'Starts a chat completion request and streams chunks via channel lmstudio.inference.{jobId}.',
  },
]
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-6 py-12">
    <header>
      <p class="text-sm font-semibold uppercase tracking-wide text-indigo-500">LM Studio</p>
      <h1 class="text-3xl font-bold text-slate-900">Backend Demo</h1>
      <p class="mt-2 text-sm text-slate-600">
        Use these endpoints to verify that the Laravel backend can talk to the LM Studio SDK.
        Subscribe to <code>lmstudio.inference.&lt;jobId&gt;</code> for streaming updates when starting
        an inference job.
      </p>
    </header>

    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="text-base font-semibold text-slate-900">API Endpoints</h2>
      <ul class="mt-4 space-y-4">
        <li
          v-for="endpoint in endpoints"
          :key="endpoint.path"
          class="rounded-md border border-slate-100 bg-slate-50 p-4"
        >
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
            {{ endpoint.method }}
          </p>
          <p class="font-mono text-sm text-indigo-600">{{ endpoint.path }}</p>
          <p class="mt-2 text-sm text-slate-600">{{ endpoint.description }}</p>
        </li>
      </ul>
    </section>

    <section class="rounded-lg border border-indigo-200 bg-indigo-50/60 p-6 text-sm text-slate-700">
      <p class="font-semibold text-indigo-900">Manual test flow</p>
      <ol class="mt-3 list-decimal space-y-2 pl-5">
        <li>Use the models endpoint to ensure LM Studio is reachable.</li>
        <li>
          POST to <code>/api/v1/ai/lmstudio/infer</code> with chat messages. The response contains a
          <code>job_id</code>.
        </li>
        <li>
          Listen to <code>lmstudio.inference.{job_id}</code> to receive <code>chunk</code> and
          <code>completed</code> events.
        </li>
      </ol>
    </section>
  </div>
</template>
