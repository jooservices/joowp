<template>
    <div class="app-layout">
        <nav class="navbar navbar-expand-lg navbar-dark top-nav shadow-sm">
            <div class="container-fluid">
                <a class="navbar-brand fw-semibold text-uppercase tracking-wide" href="/">
                    JOOwp
                </a>
                <button
                    class="navbar-toggler border-0"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#mainNav"
                    aria-controls="mainNav"
                    aria-expanded="false"
                    aria-label="Toggle navigation"
                >
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div id="mainNav" class="collapse navbar-collapse">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a
                                class="nav-link"
                                :class="{ active: isCurrent('/') }"
                                aria-current="page"
                                href="/"
                            >
                                Home
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a
                                ref="taxonomyDropdownRef"
                                class="nav-link dropdown-toggle"
                                :class="{ active: isTaxonomyActive }"
                                href="#"
                                role="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                Taxonomy
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark">
                                <li>
                                    <a
                                        class="dropdown-item"
                                        :class="{ active: isCurrent('/taxonomy/categories') }"
                                        href="/taxonomy/categories"
                                    >
                                        Categories
                                    </a>
                                </li>
                                <li>
                                    <a
                                        class="dropdown-item"
                                        :class="{ active: isCurrent('/taxonomy/tags') }"
                                        href="/taxonomy/tags"
                                    >
                                        Tags
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                    <div class="toast-stack" role="alert" aria-live="assertive" aria-atomic="true">
                        <transition-group name="toast">
                            <div
                                v-for="toast in toasts"
                                :key="toast.id"
                                class="toast-card shadow-lg"
                                :class="`toast-${toast.variant}`"
                            >
                                <div class="toast-body d-flex align-items-start gap-3">
                                    <div class="toast-icon-wrapper" aria-hidden="true">
                                        <span
                                            class="fa-solid"
                                            :class="toast.variant === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-check'"
                                        ></span>
                                    </div>
                                    <div class="toast-content flex-fill">
                                        <p class="toast-title mb-1">{{ toast.title }}</p>
                                        <p class="toast-message mb-0">{{ toast.message }}</p>
                                    </div>
                                    <button
                                        type="button"
                                        class="btn-close btn-close-white ms-2"
                                        aria-label="Close"
                                        @click="dismissToast(toast.id)"
                                    ></button>
                                </div>
                            </div>
                        </transition-group>
                    </div>
                    <template v-if="!isInitialising && !tokenStatus.remembered">
                        <form
                            class="ms-auto d-flex flex-column flex-sm-row align-items-sm-center gap-2 auth-form"
                            autocomplete="off"
                            @submit.prevent="submitCredentials"
                        >
                            <label class="visually-hidden" for="nav-username">Username</label>
                            <input
                                id="nav-username"
                                v-model="formState.username"
                                type="text"
                                class="form-control form-control-sm"
                                placeholder="Username"
                                aria-label="Username"
                                :disabled="formState.loading"
                            />
                            <label class="visually-hidden" for="nav-password">Password</label>
                            <input
                                id="nav-password"
                                v-model="formState.password"
                                type="password"
                                class="form-control form-control-sm"
                                placeholder="Password"
                                aria-label="Password"
                                :disabled="formState.loading"
                            />
                            <div class="remember-wrapper d-flex align-items-center gap-2">
                                <input
                                    id="remember-token"
                                    v-model="formState.remember"
                                    class="form-check-input remember-switch"
                                    type="checkbox"
                                    role="switch"
                                    aria-label="Remember token"
                                    :disabled="formState.loading"
                                />
                                <label class="remember-label text-light-emphasis small mb-0" for="remember-token">
                                    Remember
                                </label>
                            </div>
                            <button
                                type="submit"
                                class="btn btn-primary btn-sm px-4 shadow-sm d-flex align-items-center gap-2"
                                :disabled="isSubmitDisabled"
                            >
                                <span v-if="!formState.loading" class="fa-solid fa-arrow-right-to-bracket"></span>
                                <span
                                    v-if="formState.loading"
                                    class="spinner-border spinner-border-sm"
                                    role="status"
                                    aria-hidden="true"
                                ></span>
                                <span>{{ formState.loading ? 'Submitting…' : 'Log In' }}</span>
                            </button>
                        </form>
                        <div v-if="formState.loading" class="auth-overlay">
                            <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                        </div>
                    </template>
                    <template v-else-if="!isInitialising">
                        <div class="ms-auto d-flex flex-column flex-sm-row align-items-sm-center gap-3 remembered-summary">
                            <div class="input-group input-group-sm token-input-group">
                                <span class="input-group-text">
                                    <i class="fa-solid fa-key"></i>
                                </span>
                                <input
                                    type="text"
                                    class="form-control"
                                    :value="tokenStatus.masked_token ?? '••••••••'"
                                    readonly
                                />
                                <span v-if="tokenStatus.username" class="input-group-text token-username">
                                    @{{ tokenStatus.username }}
                                </span>
                            </div>
                            <button
                                type="button"
                                class="btn btn-danger btn-sm d-flex align-items-center gap-2 forget-btn shadow-sm"
                                :disabled="formState.loading"
                                @click="clearRememberedToken"
                            >
                                <i v-if="!formState.loading" class="fa-solid fa-trash-can"></i>
                                <span v-if="formState.loading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                <span v-else>Forget</span>
                            </button>
                        </div>
                    </template>
                    <div v-if="isInitialising" class="ms-auto d-flex align-items-center gap-3 auth-initialising">
                        <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                        <span class="text-light-emphasis small">Loading token status…</span>
                    </div>
                </div>
            </div>
        </nav>

        <main>
            <slot />
        </main>
    </div>
</template>

<script setup lang="ts">
import Dropdown from 'bootstrap/js/dist/dropdown';
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

interface Toast {
    id: string;
    variant: 'success' | 'error';
    title: string;
    message: string;
}

interface FormState {
    username: string;
    password: string;
    remember: boolean;
    loading: boolean;
}

interface TokenSummary {
    remembered: boolean;
    masked_token?: string | null;
    username?: string | null;
}

const page = usePage();
const taxonomyDropdownRef = ref<HTMLElement | null>(null);
const formState = reactive<FormState>({
    username: '',
    password: '',
    remember: false,
    loading: false,
});

const tokenStatus = reactive<TokenSummary>({
    remembered: false,
    masked_token: null,
    username: null,
});

const toasts = ref<Toast[]>([]);
const isInitialising = ref(true);
let taxonomyDropdown: Dropdown | null = null;

const currentUrl = computed(() => page.url ?? '/');
const currentPath = computed(() => currentUrl.value.split('?')[0] ?? '/');
const isTaxonomyActive = computed(() => currentUrl.value.startsWith('/taxonomy'));
const isSubmitDisabled = computed(() => {
    if (formState.loading) {
        return true;
    }

    return formState.username.trim() === '' || formState.password.trim() === '';
});

const isCurrent = (path: string): boolean => {
    const normalisedPath = path === '/' ? '/' : path.replace(/\/$/, '');
    const normalisedCurrent = currentPath.value === '/' ? '/' : currentPath.value.replace(/\/$/, '');

    if (normalisedPath === '/') {
        return normalisedCurrent === '/';
    }

    return normalisedCurrent === normalisedPath || normalisedCurrent.startsWith(`${normalisedPath}/`);
};

const dismissToast = (id: string): void => {
    toasts.value = toasts.value.filter((toast) => toast.id !== id);
};

const pushToast = (variant: Toast['variant'], title: string, message: string): void => {
    const id = window.crypto.randomUUID?.() ?? `${Date.now()}-${Math.random().toString(16).slice(2)}`;
    toasts.value.push({ id, variant, title, message });
    window.setTimeout(() => dismissToast(id), 4000);
};

const refreshTokenStatus = async (): Promise<void> => {
    try {
        const response = await window.axios.get('/api/v1/wordpress/token');
        Object.assign(tokenStatus, response.data?.data ?? { remembered: false });
    } catch (error: unknown) {
        pushToast('error', 'Token status', extractErrorMessage(error));
    }
};

const submitCredentials = async (): Promise<void> => {
    formState.loading = true;
    try {
        const response = await window.axios.post('/api/v1/wordpress/token', {
            username: formState.username,
            password: formState.password,
            remember: formState.remember,
        });

        pushToast('success', 'Token stored', response.data?.message ?? 'Token stored successfully.');
        Object.assign(tokenStatus, response.data?.data ?? {});
        formState.password = '';
    } catch (error: unknown) {
        pushToast('error', 'Token error', extractErrorMessage(error));
    } finally {
        formState.loading = false;
    }
};

const clearRememberedToken = async (): Promise<void> => {
    formState.loading = true;
    try {
        const response = await window.axios.delete('/api/v1/wordpress/token');
        Object.assign(tokenStatus, response.data?.data ?? { remembered: false });
        pushToast('success', 'Token cleared', response.data?.message ?? 'Remembered token removed.');
    } catch (error: unknown) {
        pushToast('error', 'Token error', extractErrorMessage(error));
    } finally {
        formState.loading = false;
    }
};

const extractErrorMessage = (error: unknown): string => {
    if (
        typeof error === 'object' &&
        error !== null &&
        'response' in error &&
        typeof (error as Record<string, unknown>).response === 'object'
    ) {
        const response = (error as { response?: { data?: { message?: string } } }).response;
        if (response?.data?.message) {
            return response.data.message;
        }
    }

    return 'Unexpected error communicating with WordPress.';
};

onMounted(async () => {
    await refreshTokenStatus();
    isInitialising.value = false;

    if (taxonomyDropdownRef.value) {
        taxonomyDropdown = new Dropdown(taxonomyDropdownRef.value);
    }
});

onBeforeUnmount(() => {
    taxonomyDropdown?.dispose();
    taxonomyDropdown = null;
});
</script>

<style scoped>
.top-nav {
    background: rgba(10, 15, 27, 0.92);
    backdrop-filter: blur(15px);
    border-bottom: 1px solid rgba(148, 163, 184, 0.2);
    position: sticky;
    top: 0;
    z-index: 1020;
}

.top-nav .navbar-brand {
    color: #f8fafc;
    letter-spacing: 0.08rem;
}

.top-nav .nav-link {
    font-weight: 500;
    color: rgba(248, 250, 252, 0.85);
}

.top-nav .nav-link.active,
.top-nav .nav-link:focus,
.top-nav .nav-link:hover {
    color: #60a5fa;
}

.toast-stack {
    position: fixed;
    top: 1.5rem;
    right: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    z-index: 1200;
    width: min(360px, 90vw);
}

.toast-enter-active,
.toast-leave-active {
    transition: all 0.2s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

.toast-card {
    border-radius: 1rem;
    padding: 0.9rem 1.1rem;
    background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(15, 23, 42, 0.82));
    border: 1px solid rgba(148, 163, 184, 0.18);
    color: #f8fafc;
    box-shadow: 0 20px 45px rgba(8, 11, 19, 0.35);
    backdrop-filter: blur(18px);
}

.toast-success {
    border-color: rgba(45, 212, 191, 0.55);
    box-shadow: 0 18px 35px rgba(34, 197, 94, 0.25);
}

.toast-error {
    border-color: rgba(252, 165, 165, 0.6);
    box-shadow: 0 18px 35px rgba(248, 113, 113, 0.28);
}

.toast-body {
    font-size: 0.95rem;
}

.toast-icon-wrapper {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(30, 41, 59, 0.8);
    border: 1px solid rgba(148, 163, 184, 0.2);
    color: inherit;
}

.toast-success .toast-icon-wrapper {
    background: rgba(16, 185, 129, 0.22);
    border-color: rgba(16, 185, 129, 0.4);
    color: #5ef1c5;
}

.toast-error .toast-icon-wrapper {
    background: rgba(248, 113, 113, 0.18);
    border-color: rgba(248, 113, 113, 0.45);
    color: #fda4af;
}

.toast-title {
    font-size: 0.85rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: rgba(248, 250, 252, 0.82);
}

.toast-message {
    font-size: 0.95rem;
    color: rgba(226, 232, 240, 0.9);
}

.auth-form .form-control {
    min-width: 180px;
    background: rgba(15, 23, 42, 0.8);
    border-color: rgba(148, 163, 184, 0.4);
    color: #e2e8f0;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.auth-form .form-control:focus {
    border-color: #60a5fa;
    box-shadow: 0 0 0 0.15rem rgba(96, 165, 250, 0.25);
    background: rgba(15, 23, 42, 0.95);
}

.auth-form .btn {
    white-space: nowrap;
}

.auth-form .form-control::placeholder {
    color: rgba(226, 232, 240, 0.55);
}

.auth-form {
    position: relative;
}

.auth-overlay {
    position: absolute;
    inset: -0.5rem;
    background: rgba(2, 6, 23, 0.65);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.75rem;
    backdrop-filter: blur(4px);
}

.remember-wrapper {
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    background: rgba(15, 23, 42, 0.55);
    border: 1px solid rgba(148, 163, 184, 0.18);
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.remember-switch {
    cursor: pointer;
    width: 2.8rem;
    height: 1.4rem;
    margin: 0;
}

.remember-label {
    letter-spacing: 0.05em;
    text-transform: uppercase;
    display: flex;
    align-items: center;
    height: 100%;
}

.remembered-summary {
    background: rgba(15, 23, 42, 0.5);
    border-radius: 9999px;
    border: 1px solid rgba(148, 163, 184, 0.18);
    padding: 0.35rem 0.75rem;
}

.token-input-group {
    min-width: 16rem;
    max-width: 20rem;
}

.token-input-group .input-group-text {
    background: rgba(15, 23, 42, 0.8);
    border: none;
    color: rgba(248, 250, 252, 0.85);
}

.token-input-group .form-control {
    font-family: 'SFMono-Regular', Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
    background: rgba(15, 23, 42, 0.7);
    border: none;
    color: rgba(248, 250, 252, 0.9);
    text-overflow: ellipsis;
}

.token-input-group .form-control:focus {
    box-shadow: none;
}

.token-username {
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    text-transform: lowercase;
}

.forget-btn {
    border: none;
    padding-inline: 1.1rem;
}

.auth-initialising {
    padding: 0.3rem 0.75rem;
    border-radius: 0.75rem;
    background: rgba(15, 23, 42, 0.5);
    border: 1px solid rgba(148, 163, 184, 0.2);
}
</style>

