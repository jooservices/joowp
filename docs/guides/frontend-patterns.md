# Frontend Patterns Guide

Complete guide to Vue 3, TypeScript, Inertia.js, and frontend architecture patterns following project standards.

> **Related Documentation:**
> - [Frontend Standards](../architecture/principles.md#10-frontend-standards) - TypeScript-only, dark theme, Bootstrap
> - [Error Handling Guide](error-handling.md) - Frontend error handling patterns
> - [Testing Patterns Guide](testing-patterns.md) - Frontend testing strategies

---

## Overview

This project uses:
- **Vue 3** with Composition API (`<script setup>`)
- **TypeScript** with strict mode
- **Inertia.js** for SPA-like experience
- **Bootstrap 5** + **FontAwesome** for UI
- **Dark theme** exclusively
- **Pinia** for state management

---

## Vue Component Patterns

### Component Structure

```vue
<template>
    <!-- Template content -->
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

// Define layout
defineOptions({ layout: AppLayout });

// Props
interface Props {
    title: string;
    items: Item[];
}

const props = defineProps<Props>();

// Emits
const emit = defineEmits<{
    update: [value: string];
    delete: [id: number];
}>();

// Reactive state
const isLoading = ref(false);
const errorMessage = ref<string | null>(null);

// Computed
const filteredItems = computed(() => {
    return props.items.filter(item => item.active);
});

// Methods
function handleSubmit(): void {
    // Implementation
}

// Lifecycle
onMounted(() => {
    // Initialization
});
</script>

<style scoped>
/* Component styles */
</style>
```

### Using Inertia Layouts

```vue
<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';

// Set layout for this page
defineOptions({ layout: AppLayout });
</script>
```

### Reactive State Management

```vue
<script setup lang="ts">
import { ref, reactive, computed } from 'vue';

// Simple reactive value
const count = ref(0);

// Reactive object
const form = reactive({
    name: '',
    email: '',
});

// Computed property
const isValid = computed(() => {
    return form.name.length > 0 && form.email.includes('@');
});

// Update reactive values
function increment(): void {
    count.value++;
}
</script>
```

### Props and Emits

```vue
<script setup lang="ts">
// Props with TypeScript
interface Props {
    title: string;
    items: Array<{ id: number; name: string }>;
    optional?: boolean;
}

const props = defineProps<Props>();

// Emits with TypeScript
const emit = defineEmits<{
    update: [id: number, value: string];
    delete: [id: number];
}>();

function handleUpdate(id: number, value: string): void {
    emit('update', id, value);
}
</script>
```

---

## TypeScript Patterns

### Type Definitions

```typescript
// Interface for API responses
interface ApiResponse<T> {
    ok: boolean;
    code: string;
    status: number;
    message: string;
    data: T;
    meta?: Record<string, unknown>;
}

// Interface for form data
interface CategoryForm {
    name: string;
    slug: string;
    description?: string;
    parent_id?: number;
}

// Type for component props
type ButtonVariant = 'primary' | 'secondary' | 'danger';
```

### API Client Types

```typescript
// SDK client interface
interface LmStudioClient {
    listModels(): Promise<ModelSummary[]>;
    infer(request: InferenceRequest): Promise<InferenceResponse>;
}

// Request/Response types
interface InferenceRequest {
    model: string;
    messages: Array<{ role: string; content: string }>;
    temperature?: number;
}

interface InferenceResponse {
    job_id: string;
    model: string;
    created: number;
}
```

### Type Guards

```typescript
function isApiError(error: unknown): error is ApiErrorResponse {
    return (
        typeof error === 'object' &&
        error !== null &&
        'ok' in error &&
        'code' in error &&
        'status' in error
    );
}

// Usage
try {
    await axios.post('/api/v1/products', data);
} catch (error) {
    if (isApiError(error)) {
        console.error(error.code, error.message);
    }
}
```

---

## API Client Patterns

### Axios Configuration

```typescript
import axios from 'axios';

// Configure base URL
axios.defaults.baseURL = '/api/v1';

// Add request interceptor
axios.interceptors.request.use((config) => {
    // Add auth token if available
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Add response interceptor
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        // Handle errors globally
        if (error.response?.status === 401) {
            // Redirect to login
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);
```

### SDK Client Pattern

```typescript
// resources/js/sdk/lmStudio/client.ts
import axios from 'axios';
import type { ModelSummary, InferenceRequest, InferenceResponse } from './types';

class LmStudioClient {
    private baseURL = '/api/v1/ai/lmstudio';

    async listModels(): Promise<ModelSummary[]> {
        const { data } = await axios.get<ApiResponse<{ models: ModelSummary[] }>>(
            `${this.baseURL}/models`
        );
        return data.data.models;
    }

    async infer(request: InferenceRequest): Promise<InferenceResponse> {
        const { data } = await axios.post<ApiResponse<InferenceResponse>>(
            `${this.baseURL}/infer`,
            request
        );
        return data.data;
    }
}

export const lmStudioClient = new LmStudioClient();
```

### Composable Pattern

```typescript
// resources/js/sdk/lmStudio/composables/useLmStudio.ts
import { ref, computed } from 'vue';
import { lmStudioClient } from '../client';
import type { ModelSummary } from '../types';

export function useLmStudio() {
    const models = ref<ModelSummary[]>([]);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    const hasModels = computed(() => models.value.length > 0);

    async function fetchModels(): Promise<void> {
        isLoading.value = true;
        error.value = null;
        
        try {
            models.value = await lmStudioClient.listModels();
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to fetch models';
        } finally {
            isLoading.value = false;
        }
    }

    return {
        models,
        isLoading,
        error,
        hasModels,
        fetchModels,
    };
}
```

### Using Composables in Components

```vue
<script setup lang="ts">
import { onMounted } from 'vue';
import { useLmStudio } from '@/sdk/lmStudio/composables/useLmStudio';

const { models, isLoading, error, fetchModels } = useLmStudio();

onMounted(() => {
    fetchModels();
});
</script>

<template>
    <div v-if="isLoading">Loading models...</div>
    <div v-else-if="error">{{ error }}</div>
    <div v-else>
        <div v-for="model in models" :key="model.id">
            {{ model.id }}
        </div>
    </div>
</template>
```

---

## State Management with Pinia

### Store Definition

```typescript
// stores/user.ts
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
}

export const useUserStore = defineStore('user', () => {
    const user = ref<User | null>(null);
    const isAuthenticated = computed(() => user.value !== null);

    function setUser(newUser: User): void {
        user.value = newUser;
    }

    function clearUser(): void {
        user.value = null;
    }

    return {
        user,
        isAuthenticated,
        setUser,
        clearUser,
    };
});
```

### Using Stores in Components

```vue
<script setup lang="ts">
import { useUserStore } from '@/stores/user';

const userStore = useUserStore();

function handleLogin(user: User): void {
    userStore.setUser(user);
}
</script>

<template>
    <div v-if="userStore.isAuthenticated">
        Welcome, {{ userStore.user?.name }}
    </div>
</template>
```

---

## UI Patterns

### Bootstrap Components

```vue
<template>
    <!-- Buttons -->
    <button type="button" class="btn btn-primary">Primary</button>
    <button type="button" class="btn btn-outline-light">Outline</button>
    
    <!-- Cards -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Title</h5>
            <p class="card-text">Content</p>
        </div>
    </div>
    
    <!-- Forms -->
    <form @submit.prevent="handleSubmit">
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input
                id="name"
                v-model="form.name"
                type="text"
                class="form-control"
                required
            />
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
    
    <!-- Alerts -->
    <div v-if="errorMessage" class="alert alert-danger" role="alert">
        {{ errorMessage }}
    </div>
</template>
```

### FontAwesome Icons

```vue
<template>
    <!-- Icons -->
    <span class="fa-solid fa-user"></span>
    <span class="fa-solid fa-trash"></span>
    <span class="fa-solid fa-check text-success"></span>
    <span class="fa-solid fa-times text-danger"></span>
</template>
```

### Dark Theme Styling

```vue
<style scoped>
.card {
    background: rgba(15, 23, 42, 0.85);
    border: 1px solid rgba(148, 163, 184, 0.15);
    color: #f9fafb;
}

.btn-primary {
    background: #2563eb;
    border-color: #2563eb;
}

.text-secondary {
    color: rgba(226, 232, 240, 0.85) !important;
}
</style>
```

---

## Form Handling

### Form Validation

```vue
<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';

interface FormData {
    name: string;
    email: string;
}

const form = ref<FormData>({
    name: '',
    email: '',
});

const errors = ref<Record<string, string[]>>({});

const isValid = computed(() => {
    return form.value.name.length > 0 && form.value.email.includes('@');
});

async function handleSubmit(): Promise<void> {
    errors.value = {};
    
    try {
        await axios.post('/api/v1/users', form.value);
        // Success handling
    } catch (error) {
        if (axios.isAxiosError(error) && error.response?.status === 422) {
            errors.value = error.response.data.meta?.errors || {};
        }
    }
}
</script>

<template>
    <form @submit.prevent="handleSubmit">
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input
                id="name"
                v-model="form.name"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': errors.name }"
            />
            <div v-if="errors.name" class="invalid-feedback">
                {{ errors.name[0] }}
            </div>
        </div>
        <button type="submit" class="btn btn-primary" :disabled="!isValid">
            Submit
        </button>
    </form>
</template>
```

---

## Error Handling

### Toast Notifications

```vue
<script setup lang="ts">
import { toast } from 'vue3-toastify';
import axios from 'axios';

async function createProduct(data: ProductForm): Promise<void> {
    try {
        await axios.post('/api/v1/products', data);
        toast.success('Product created successfully');
    } catch (error) {
        if (axios.isAxiosError(error)) {
            const response = error.response;
            if (response?.status === 422) {
                toast.error('Validation failed');
            } else {
                toast.error(response?.data?.message || 'An error occurred');
            }
        }
    }
}
</script>
```

### Error Display Component

```vue
<template>
    <div v-if="error" class="alert alert-danger" role="alert">
        <span class="fa-solid fa-exclamation-circle me-2"></span>
        {{ error }}
    </div>
</template>

<script setup lang="ts">
defineProps<{
    error: string | null;
}>();
</script>
```

---

## Best Practices

### ✅ DO

- **Use TypeScript** - All components and scripts must use TypeScript
- **Use Composition API** - Prefer `<script setup>` syntax
- **Define types** - Create interfaces for props, emits, and data
- **Use composables** - Extract reusable logic into composables
- **Follow dark theme** - All UI must use dark theme exclusively
- **Use Bootstrap classes** - Leverage Bootstrap 5 utility classes
- **Handle errors** - Always handle API errors gracefully
- **Validate inputs** - Client-side validation before submission

### ❌ DON'T

- **Don't use Options API** - Use Composition API only
- **Don't use `any` type** - Always define proper types
- **Don't call APIs directly** - Use SDK clients or composables
- **Don't mix themes** - Dark theme only, no light theme
- **Don't skip error handling** - Always handle API errors
- **Don't use inline styles** - Use scoped CSS or Bootstrap classes
- **Don't mutate props** - Use emits to update parent state

---

## Related Documentation

- [Frontend Standards](../architecture/principles.md#10-frontend-standards) - TypeScript, dark theme requirements
- [Error Handling Guide](error-handling.md) - Frontend error patterns
- [Testing Patterns Guide](testing-patterns.md) - Frontend testing

