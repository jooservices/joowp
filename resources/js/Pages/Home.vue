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
                    <form class="ms-auto d-flex flex-column flex-sm-row gap-2 auth-form" autocomplete="off">
                        <label class="visually-hidden" for="nav-username">Username</label>
                        <input
                            id="nav-username"
                            type="text"
                            class="form-control form-control-sm"
                            placeholder="Username"
                            aria-label="Username"
                        />
                        <label class="visually-hidden" for="nav-password">Password</label>
                        <input
                            id="nav-password"
                            type="password"
                            class="form-control form-control-sm"
                            placeholder="Password"
                            aria-label="Password"
                        />
                        <button type="button" class="btn btn-primary btn-sm px-4 shadow-sm">
                            Log In
                        </button>
                    </form>
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
import { onMounted, ref } from 'vue';

const STORAGE_KEY = 'joowp.welcome.seen';
const strapline = 'Modular Laravel • WordPress Ready • Dark Aesthetic';
const displayWelcome = ref(false);

const setSeen = (): void => window.localStorage.setItem(STORAGE_KEY, 'true');

onMounted(() => {
    const hasSeen = window.localStorage.getItem(STORAGE_KEY);
    displayWelcome.value = !hasSeen;

    if (!hasSeen) {
        setSeen();
    }
});

const dismissWelcome = (): void => {
    displayWelcome.value = false;
};

const resetWelcome = (): void => {
    window.localStorage.removeItem(STORAGE_KEY);
    displayWelcome.value = true;
    setSeen();
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
    color: #f8fafc;
}

.auth-form .form-control:focus {
    border-color: #60a5fa;
    box-shadow: 0 0 0 0.15rem rgba(96, 165, 250, 0.25);
}

.auth-form .btn {
    white-space: nowrap;
}
</style>
