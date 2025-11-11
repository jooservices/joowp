<template>
    <div class="welcome-wrapper">
        <nav class="navbar navbar-expand-lg navbar-dark top-nav shadow-sm">
            <div class="container-fluid">
                <a class="navbar-brand fw-semibold text-uppercase tracking-wide" href="/">
                    JOOwp
                </a>
                <button
                    class="navbar-toggler border-0"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#welcomeNav"
                    aria-controls="welcomeNav"
                    aria-expanded="false"
                    aria-label="Toggle navigation"
                >
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div id="welcomeNav" class="collapse navbar-collapse">
                    <div class="navbar-nav">
                        <a class="nav-link active" aria-current="page" href="/">Home</a>
                    </div>
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
                                type="text"
                                class="form-control form-control-sm"
                                placeholder="Username"
                                aria-label="Username"
                                v-model="formState.username"
                                :disabled="formState.loading"
                            />
                            <label class="visually-hidden" for="nav-password">Password</label>
                            <input
                                id="nav-password"
                                type="password"
                                class="form-control form-control-sm"
                                placeholder="Password"
                                aria-label="Password"
                                v-model="formState.password"
                                :disabled="formState.loading"
                            />
                            <div class="remember-wrapper d-flex align-items-center gap-2">
                                <input
                                    id="remember-token"
                                    class="form-check-input remember-switch"
                                    type="checkbox"
                                    role="switch"
                                    aria-label="Remember token"
                                    v-model="formState.remember"
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
                                <span v-if="formState.loading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                <span>{{ formState.loading ? 'Submitting…' : 'Log In' }}</span>
                            </button>
                        </form>
                        <div v-if="formState.loading" class="auth-overlay">
                            <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                        </div>
                    </template>
                    <template v-else-if="!isInitialising">
                        <div class="ms-auto d-flex flex-column flex-sm-row align-items-sm-center gap-3 remembered-summary">
                            <div class="token-chip">
                                <span class="chip-label">Stored Token</span>
                                <span class="chip-value">{{ tokenStatus.maskedToken ?? '••••••••' }}</span>
                                <span v-if="tokenStatus.username" class="chip-caption">@{{ tokenStatus.username }}</span>
                            </div>
                            <button
                                type="button"
                                class="btn btn-outline-light btn-sm px-4 shadow-sm"
                                @click="clearRememberedToken"
                                :disabled="isClearingToken"
                            >
                                {{ isClearingToken ? 'Clearing…' : 'Forget' }}
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
        <section v-if="displayWelcome" class="hero container">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12 col-lg-6 text-center text-lg-start">
                    <span class="badge rounded-pill bg-secondary-subtle text-uppercase tracking-wide mb-3">
                        {{ strapline }}
                    </span>
                    <h1 class="display-4 fw-bold lh-1 text-gradient mb-4">
                        Welcome to JOOwp
                    </h1>
                    <p class="lead text-light-emphasis mb-4">
                        A modern, modular Laravel platform crafted for WordPress-powered experiences. TypeScript-first frontend,
                        SOLID backend design, and comprehensive automation—ready for your next project launch.
                    </p>
                    <div class="d-flex flex-column flex-sm-row gap-3">
                        <button type="button" class="btn btn-primary btn-lg px-4 shadow" @click="dismissWelcome">
                            Enter Platform
                        </button>
                        <a
                            class="btn btn-outline-light btn-lg px-4"
                            href="docs/principles.md"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Read Engineering Principles
                        </a>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="glass-panel shadow-lg">
                        <div class="row g-4">
                            <article class="col-12">
                                <div class="feature-tile text-start">
                                    <div class="icon-wrapper text-primary mb-3">
                                        <span class="fa-solid fa-layer-group fa-2x"></span>
                                    </div>
                                    <h2 class="h4 text-white mb-2">Modular by Design</h2>
                                    <p class="text-secondary mb-0">
                                        Each business capability lives in its own module. Shared services reside in the `Core`
                                        module for clean cross-cutting boundaries.
                                    </p>
                                </div>
                            </article>
                            <article class="col-12">
                                <div class="feature-tile text-start">
                                    <div class="icon-wrapper text-info mb-3">
                                        <span class="fa-solid fa-code-branch fa-2x"></span>
                                    </div>
                                    <h2 class="h4 text-white mb-2">WordPress REST SDK</h2>
                                    <p class="text-secondary mb-0">
                                        Guzzle-powered SDK wraps the WordPress REST API for reliable posts, media, taxonomy, and
                                        search integrations.
                                    </p>
                                </div>
                            </article>
                            <article class="col-12">
                                <div class="feature-tile text-start">
                                    <div class="icon-wrapper text-success mb-3">
                                        <span class="fa-solid fa-shield-halved fa-2x"></span>
                                    </div>
                                    <h2 class="h4 text-white mb-2">Quality First</h2>
                                    <p class="text-secondary mb-0">
                                        Mandatory unit tests, enforced static analysis, and dark-themed UI standards keep the
                                        platform polished and predictable.
                                    </p>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section v-else class="container py-5 returning">
                    <div class="card returning-card border-0 shadow-sm">
                <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                    <div>
                                <h2 class="h3 text-light mb-2">Welcome back to JOOwp</h2>
                                <p class="text-muted mb-0">
                            You have already seen the onboarding experience. Dive straight into your workflow or revisit the
                            guides whenever you need a refresher.
                        </p>
                    </div>
                    <div class="d-flex gap-3">
                        <a
                            class="btn btn-primary px-4"
                            href="docs/guides/core-wordpress-sdk.md"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            WordPress SDK Guide
                        </a>
                        <button type="button" class="btn btn-outline-light px-4" @click="resetWelcome">
                            Replay Welcome
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { isAxiosError } from 'axios';

const STORAGE_KEY = 'joowp.welcome.seen';
const strapline = 'Modular Laravel • WordPress Ready • Dark Aesthetic';
const displayWelcome = ref(false);

interface FormState {
    username: string;
    password: string;
    loading: boolean;
    remember: boolean;
}

interface Toast {
    id: number;
    message: string;
    title: string;
    variant: 'success' | 'error';
    sticky: boolean;
}

interface ApiResponse<TData> {
    ok: boolean;
    code: string;
    status: number;
    message: string;
    data: TData | null;
    meta: Record<string, unknown>;
}

interface TokenPayload {
    id: number | null;
    remembered: boolean;
    masked_token: string | null;
}

interface TokenStatusPayload {
    remembered: boolean;
    masked_token: string | null;
    username?: string;
}

type TokenResponse = ApiResponse<TokenPayload>;
type TokenStatusResponse = ApiResponse<TokenStatusPayload>;

const formState = reactive<FormState>({
    username: '',
    password: '',
    loading: false,
    remember: false,
});

const toasts = ref<Toast[]>([]);
const tokenStatus = reactive<{ remembered: boolean; maskedToken: string | null; username: string | null }>({
    remembered: false,
    maskedToken: null,
    username: null,
});
const isInitialising = ref(true);
const isClearingToken = ref(false);

const isSubmitDisabled = computed<boolean>(() => {
    if (formState.loading) {
        return true;
    }

    return formState.username.trim() === '' || formState.password.trim() === '';
});

const setSeen = (): void => window.localStorage.setItem(STORAGE_KEY, 'true');

onMounted(() => {
    const hasSeen = window.localStorage.getItem(STORAGE_KEY);
    displayWelcome.value = !hasSeen;

    if (!hasSeen) {
        setSeen();
    }

    void loadRememberedToken();
});

const dismissWelcome = (): void => {
    displayWelcome.value = false;
};

const resetWelcome = (): void => {
    window.localStorage.removeItem(STORAGE_KEY);
    displayWelcome.value = true;
    setSeen();
};

const submitCredentials = async (): Promise<void> => {
    formState.loading = true;

    try {
        const response = await window.axios.post<TokenResponse>('/api/v1/wordpress/token', {
            username: formState.username,
            password: formState.password,
            remember: formState.remember,
        });

        const payload = response.data;

        if (!payload.ok) {
            const metaStatus = extractSourceStatus(payload.meta);
            const message = metaStatus
                ? `${payload.message} (Upstream status: ${metaStatus})`
                : payload.message;

            addToast(
                message,
                'error',
                true,
                titleFromCode(payload.code, 'error')
            );

            return;
        }

        updateTokenStatusFromPayload(payload.data);

        addToast(payload.message, 'success', false, titleFromCode(payload.code, 'success'));
        formState.password = '';
        formState.remember = payload.data?.remembered ?? formState.remember;
    } catch (error: unknown) {
        if (isAxiosError(error) && error.response) {
            const fallback = 'Unable to authenticate with WordPress.';
            const message = (error.response.data as Record<string, unknown>)?.message;
            const meaningfulMessage =
                typeof message === 'string' && message.trim() !== '' ? message : fallback;

            addToast(meaningfulMessage, 'error', true, 'WordPress Request Failed');
        } else {
            addToast('Unexpected error while contacting the API.', 'error', true, 'Unexpected Error');
        }
    } finally {
        formState.loading = false;
    }
};

const addToast = (
    message: string,
    variant: Toast['variant'],
    sticky = false,
    title?: string
): void => {
    const toast: Toast = {
        id: Date.now() + Math.floor(Math.random() * 1000),
        message,
        title: title ?? defaultToastTitle(variant),
        variant,
        sticky,
    };

    toasts.value.push(toast);

    if (!sticky) {
        window.setTimeout(() => dismissToast(toast.id), 5000);
    }
};

const dismissToast = (id: number): void => {
    toasts.value = toasts.value.filter((toast) => toast.id !== id);
};

const defaultToastTitle = (variant: Toast['variant']): string =>
    variant === 'success' ? 'Success' : 'Action Required';

const titleFromCode = (code: string, variant: Toast['variant']): string => {
    if (variant === 'success') {
        if (code === 'wordpress.token_created') {
            return 'Token Stored';
        }
        if (code === 'wordpress.token_cleared') {
            return 'Token Cleared';
        }
        if (code === 'wordpress.token_remembered') {
            return 'Remembered Token';
        }

        return 'Success';
    }

    if (code === 'wordpress.token_failed') {
        return 'WordPress Request Failed';
    }

    return 'Action Required';
};

const extractSourceStatus = (meta: Record<string, unknown>): number | null => {
    const status = meta.source_status;

    return typeof status === 'number' ? status : null;
};

const loadRememberedToken = async (): Promise<void> => {
    try {
        const response = await window.axios.get<TokenStatusResponse>('/api/v1/wordpress/token');
        const payload = response.data;

        if (!payload.ok) {
            return;
        }

        updateTokenStatus(payload.data);
    } catch (error: unknown) {
        addToast('Unable to load remembered token.', 'error', true, 'WordPress Request Failed');
    } finally {
        isInitialising.value = false;
    }
};

const updateTokenStatus = (data: TokenStatusPayload | null): void => {
    const remembered = data?.remembered ?? false;

    tokenStatus.remembered = remembered;
    tokenStatus.maskedToken = data?.masked_token ?? null;
    tokenStatus.username = data?.username ?? null;
    formState.remember = remembered;
    formState.username = remembered && data?.username ? data.username : '';
    formState.password = '';
};

const updateTokenStatusFromPayload = (data: TokenPayload | null): void => {
    const remembered = data?.remembered ?? false;
    tokenStatus.remembered = remembered;
    tokenStatus.maskedToken = data?.masked_token ?? null;

    if (!remembered) {
        tokenStatus.username = null;
        return;
    }

    tokenStatus.username = formState.username.trim() !== '' ? formState.username.trim() : tokenStatus.username;
};

const clearRememberedToken = async (): Promise<void> => {
    if (isClearingToken.value) {
        return;
    }

    isClearingToken.value = true;

    try {
        const response = await window.axios.delete<TokenStatusResponse>('/api/v1/wordpress/token');

        if (response.data.ok) {
            updateTokenStatus(response.data.data);
            addToast(response.data.message, 'success', false, titleFromCode(response.data.code, 'success'));
            formState.username = '';
            formState.password = '';
            formState.remember = false;
        } else {
            addToast(response.data.message, 'error', true, titleFromCode(response.data.code, 'error'));
        }
    } catch (error: unknown) {
        addToast('Unable to clear remembered token.', 'error', true, 'Unexpected Error');
    } finally {
        isClearingToken.value = false;
    }
};
</script>

<style scoped>
.welcome-wrapper {
    min-height: 100vh;
    background: radial-gradient(circle at top left, #202635, #0d0f16 55%);
    color: #f9fafb;
}

.hero {
    padding-top: 6rem;
    padding-bottom: 6rem;
}

.text-gradient {
    background: linear-gradient(135deg, #60a5fa, #22d3ee 60%, #a855f7);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
            background-clip: text;
}

.glass-panel {
    background: rgba(17, 24, 39, 0.65);
    border-radius: 1.5rem;
    border: 1px solid rgba(148, 163, 184, 0.2);
    padding: 2.5rem;
    backdrop-filter: blur(18px);
}

.feature-tile {
    padding: 1.5rem;
    border-radius: 1.25rem;
    background: rgba(15, 23, 42, 0.75);
    border: 1px solid rgba(148, 163, 184, 0.15);
    transition: transform 0.25s ease, border-color 0.25s ease;
}

.feature-tile:hover {
    transform: translateY(-6px);
    border-color: rgba(96, 165, 250, 0.4);
}

.icon-wrapper {
    width: 3rem;
    height: 3rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.75rem;
    background: rgba(148, 163, 184, 0.1);
}

.returning .card {
    background: rgba(15, 23, 42, 0.85);
}

.tracking-wide {
    letter-spacing: 0.1rem;
}

.returning-card .card-body {
    color: #e2e8f0;
}

.returning-card .card-body .text-muted {
    color: rgba(226, 232, 240, 0.7) !important;
}

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
    padding: 0.4rem 0.75rem;
}

.token-chip {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
    max-width: 10rem;
    overflow: hidden;
}

.chip-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: rgba(226, 232, 240, 0.6);
}

.chip-value {
    font-family: 'SFMono-Regular', Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
    font-size: 0.95rem;
    color: rgba(248, 250, 252, 0.95);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chip-caption {
    font-size: 0.75rem;
    color: rgba(148, 163, 184, 0.8);
}

.auth-initialising {
    padding: 0.3rem 0.75rem;
    border-radius: 0.75rem;
    background: rgba(15, 23, 42, 0.5);
    border: 1px solid rgba(148, 163, 184, 0.2);
}

.toast-stack {
    position: fixed;
    top: 1.5rem;
    right: 1.5rem;
    z-index: 1100;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.toast-card {
    min-width: 280px;
    max-width: 340px;
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

.toast-card .toast-body {
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

.toast-enter-active,
.toast-leave-active {
    transition: all 0.25s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
