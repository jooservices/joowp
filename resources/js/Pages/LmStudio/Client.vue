<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface JobPayload {
  uuid: string
  prompt_message: string
  respond_message: string | null
  started_at: string | null
  completed_at: string | null
  prompt_tokens?: number | null
  completion_tokens?: number | null
  total_tokens?: number | null
}

interface RolePayload {
  id: number
  role_name: string
  role_prompt: string
}

const prompt = ref('')
const responseText = ref('')
const jobId = ref<string | null>(null)
const status = ref<'idle' | 'pending' | 'completed'>('idle')
const errorMessage = ref<string | null>(null)
const pollingInterval = ref<number | null>(null)
const roles = ref<RolePayload[]>([])
const selectedRoleId = ref<number | null>(null)
const rolesError = ref<string | null>(null)
const rolesLoading = ref(true)

async function submit() {
  if (prompt.value.trim() === '') {
    errorMessage.value = 'Prompt is required.'
    return
  }

  errorMessage.value = null
  responseText.value = ''
  status.value = 'pending'

  try {
    const roleId = selectedRoleId.value ?? roles.value[0]?.id ?? null
    const { data } = await axios.post('/api/v1/ai/lmstudio/jobs', {
      prompt_message: prompt.value,
      role: 'user',
      lm_studio_role_id: roleId,
    })

    jobId.value = data.data.uuid
    startPolling()
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Failed to queue job.'
    status.value = 'idle'
  }
}

function startPolling() {
  stopPolling()

  pollingInterval.value = window.setInterval(async () => {
    if (!jobId.value) return

    try {
      const { data } = await axios.get(`/api/v1/ai/lmstudio/jobs/${jobId.value}`)
      const payload = data.data as JobPayload
      if (payload.respond_message) {
        responseText.value = payload.respond_message
        status.value = 'completed'
        stopPolling()
      }
    } catch (error) {
      errorMessage.value = error instanceof Error ? error.message : 'Failed to poll job.'
      stopPolling()
      status.value = 'idle'
    }
  }, 1500)
}

function stopPolling() {
  if (pollingInterval.value) {
    window.clearInterval(pollingInterval.value)
    pollingInterval.value = null
  }
}

onMounted(() => {
  loadRoles()
  if (jobId.value) {
    startPolling()
  }
})

onBeforeUnmount(() => {
  stopPolling()
})

async function loadRoles() {
  rolesLoading.value = true
  rolesError.value = null
  try {
    const { data } = await axios.get('/api/v1/ai/lmstudio/roles')
    roles.value = data.data.roles
    if (!selectedRoleId.value && roles.value.length > 0) {
      selectedRoleId.value = roles.value[0]?.id ?? null
    }
  } catch (error) {
    rolesError.value = error instanceof Error ? error.message : 'Failed to load roles.'
  } finally {
    rolesLoading.value = false
  }
}
</script>

<template>
  <div class="categories-page container-fluid py-5 text-white">
    <header class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-5 gap-3">
      <div>
        <p class="text-uppercase text-secondary mb-2 small">LM Studio Queue</p>
        <h1 class="display-6 mb-2">Background Inference</h1>
        <p class="text-secondary mb-0">
          Submit prompts that will be queued, dispatched to LM Studio, and persisted for auditing. This view mirrors the taxonomy
          console so operational flows stay consistent.
        </p>
      </div>
      <button type="button" class="btn btn-outline-light btn-sm" @click="submit" :disabled="status === 'pending'">
        <span class="me-2 fa-solid fa-paper-plane"></span>
        {{ status === 'pending' ? 'Submitting…' : 'Submit prompt' }}
      </button>
    </header>

    <div class="row gy-4">
      <div class="col-12 col-xl-8">
        <section class="card bg-dark border-0 shadow-sm h-100">
          <div class="card-body d-flex flex-column gap-4">
            <div>
              <label class="text-secondary small text-uppercase">Prompt message</label>
              <textarea
                v-model="prompt"
                rows="8"
                class="form-control bg-transparent border-secondary-subtle text-white mt-2"
                placeholder="Describe what you want LM Studio to generate..."
              />
              <p v-if="errorMessage" class="text-danger small mt-2 mb-0">{{ errorMessage }}</p>
              <p v-if="jobId" class="text-secondary small mb-0">Tracking UUID: {{ jobId }}</p>
            </div>
            <div class="d-flex flex-column gap-2">
              <label class="text-secondary small text-uppercase">Role profile</label>
              <select
                v-model.number="selectedRoleId"
                class="form-select form-select-sm bg-transparent text-white border-secondary-subtle"
                :disabled="rolesLoading"
              >
                <option v-for="role in roles" :key="role.id" :value="role.id">
                  {{ role.role_name }}
                </option>
              </select>
              <p v-if="rolesError" class="text-danger small mb-0">
                {{ rolesError }}
                <button type="button" class="btn btn-link btn-sm p-0 ms-2 align-baseline" @click="loadRoles">
                  Retry
                </button>
              </p>
              <p v-else class="text-secondary small mb-0">
                {{ roles.find((role) => role.id === selectedRoleId)?.role_prompt ?? 'Select a role to customise the system prompt.' }}
              </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
              <button type="button" class="btn btn-primary" :disabled="status === 'pending'" @click="submit">
                <span class="fa-solid fa-cloud-arrow-up me-2"></span>
                Queue prompt
              </button>
              <button
                v-if="status === 'pending'"
                type="button"
                class="btn btn-outline-light d-inline-flex align-items-center gap-2"
                disabled
              >
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Awaiting response
              </button>
            </div>
          </div>
        </section>
      </div>

      <div class="col-12 col-xl-4">
        <section class="card bg-dark border-0 shadow-sm sticky-form">
          <div class="card-body d-flex flex-column gap-3">
            <div class="d-flex justify-content-between align-items-center">
              <h2 class="h5 text-white mb-0">Response console</h2>
              <span
                class="badge rounded-pill text-uppercase"
                :class="{
                  'text-bg-success': status === 'completed',
                  'text-bg-warning': status === 'pending',
                  'text-bg-secondary': status === 'idle',
                }"
              >
                {{ status }}
              </span>
            </div>
            <textarea
              class="form-control bg-black border-secondary-subtle text-white"
              rows="10"
              readonly
              :value="responseText"
              placeholder="Output will populate once the queued job finishes."
            />
            <small class="text-secondary">
              This panel polls every 1.5s for the latest record. Stop polling by closing the page or navigating away.
            </small>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

<style scoped>
.categories-page {
  min-height: calc(100vh - 6rem);
  background: linear-gradient(180deg, #1a2234 0%, #101726 55%, #0d1421 100%);
}

.categories-page .card {
  background: #1f2a3c !important;
  border: 1px solid rgba(58, 72, 99, 0.6) !important;
  box-shadow: 0 18px 35px rgba(10, 13, 22, 0.45);
}

.categories-page header p,
.categories-page header h1 {
  color: rgba(241, 244, 251, 0.96);
}

.categories-page textarea,
.categories-page select {
  color: rgba(240, 245, 252, 0.96);
  background: rgba(39, 53, 76, 0.8);
  border-color: rgba(78, 99, 135, 0.5);
}

.categories-page textarea::placeholder,
.categories-page select option {
  color: rgba(159, 174, 203, 0.8);
}

.categories-page .btn-outline-light {
  color: #8faadc;
  border-color: rgba(143, 170, 220, 0.45);
}

.categories-page .btn-outline-light:hover,
.categories-page .btn-outline-light:focus {
  color: #0f172a;
  background: linear-gradient(135deg, #8faadc, #a5b8e6);
  border-color: #8faadc;
}

.categories-page .btn-primary {
  background: linear-gradient(135deg, #4e73df, #2e59d9);
  border: none;
  box-shadow: 0 10px 25px rgba(46, 89, 217, 0.35);
}
</style>
