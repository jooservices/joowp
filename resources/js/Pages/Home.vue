<template>
    <div class="welcome-wrapper">
        <section v-if="displayWelcome" class="hero container content-section">
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

        <section v-else class="container py-5 returning content-section">
            <div class="card returning-card border-0 shadow-sm">
                <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                    <div>
                        <h2 class="h3 text-light mb-2">Welcome back to JOOwp</h2>
                        <p class="returning-subtitle mb-0">
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

        <section class="container-fluid px-0 content-section taxonomy-highlight">
            <div class="row g-0">
                <div id="categories-section" class="col-12 col-lg-6">
                    <div class="info-card h-100 border-0 rounded-0 rounded-end-lg">
                        <div class="info-card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h3 class="h4 text-white mb-0">
                                    <span class="fa-solid fa-folder-open me-2 text-primary"></span>
                                    Categories Management
                                </h3>
                                <span class="badge text-bg-dark border border-primary-subtle fw-normal">
                                    Core content pillars
                                </span>
                            </div>
                            <p class="text-secondary mb-4">
                                Curate foundational content groups inside WordPress to drive navigation, SEO, and editorial
                                governance for JOOwp experiences. Create, audit, and retire categories with the same guardrails
                                enforced in Laravel modules.
                            </p>
                            <div class="feature-list mb-4">
                                <div class="feature-list-item">
                                    <span class="fa-solid fa-check text-primary me-2"></span>
                                    Instant sync between WordPress taxonomies and Laravel navigation.
                                </div>
                                <div class="feature-list-item">
                                    <span class="fa-solid fa-check text-primary me-2"></span>
                                    Audit-friendly naming conventions enforced by the Core SDK.
                                </div>
                                <div class="feature-list-item">
                                    <span class="fa-solid fa-check text-primary me-2"></span>
                                    Structured metadata for hero assets and SEO copy blocks.
                                </div>
                            </div>
                            <ul class="list-unstyled taxonomy-list">
                                <li v-for="category in categoryHighlights" :key="category.name">
                                    <h4 class="h6 text-white mb-1">{{ category.name }}</h4>
                                    <p class="text-secondary mb-0">{{ category.description }}</p>
                                </li>
                            </ul>
                            <a class="btn btn-outline-light btn-sm mt-4" href="/taxonomy/categories">
                                Explore Categories
                            </a>
                        </div>
                    </div>
                </div>
                <div id="tags-section" class="col-12 col-lg-6">
                    <div class="info-card info-card-alt h-100 border-0 rounded-0 rounded-start-lg">
                        <div class="info-card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h3 class="h4 text-white mb-0">
                                    <span class="fa-solid fa-tags me-2 text-info"></span>
                                    Tags Management
                                </h3>
                                <span class="badge text-bg-dark border border-info-subtle fw-normal">
                                    Micro-topics
                                </span>
                            </div>
                            <p class="text-secondary mb-4">
                                Capture granular signals for personalization and search. Tag workflows connect WordPress editors
                                with Laravel feature toggles, campaign tracking, and experimentation flags.
                            </p>
                            <div class="feature-list mb-4">
                                <div class="feature-list-item">
                                    <span class="fa-solid fa-check text-info me-2"></span>
                                    Semantic tagging rules keep naming consistent across modules.
                                </div>
                                <div class="feature-list-item">
                                    <span class="fa-solid fa-check text-info me-2"></span>
                                    Auto-generated tag bundles for launch campaigns and retrospectives.
                                </div>
                                <div class="feature-list-item">
                                    <span class="fa-solid fa-check text-info me-2"></span>
                                    Inertia components read tags to toggle UI experiments in real time.
                                </div>
                            </div>
                            <ul class="list-unstyled taxonomy-list">
                                <li v-for="tag in tagHighlights" :key="tag.name">
                                    <h4 class="h6 text-white mb-1">{{ tag.name }}</h4>
                                    <p class="text-secondary mb-0">{{ tag.description }}</p>
                                </li>
                            </ul>
                            <a class="btn btn-outline-light btn-sm mt-4" href="/taxonomy/tags">
                                Explore Tags
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <footer class="site-footer mt-auto">
            <div class="container py-5">
                <div class="row gy-4">
                    <div class="col-12 col-lg-4">
                        <h3 class="h5 text-white mb-3">{{ appName }}</h3>
                        <p class="text-secondary mb-4">
                            Modular Laravel infrastructure purpose-built for WordPress-led experiences. Ship dark-themed,
                            rigorously tested features with total confidence.
                        </p>
                        <a
                            class="btn btn-outline-light btn-sm px-4"
                            href="docs/code-quality.md"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Review Quality Workflow
                        </a>
                    </div>
                    <div class="col-12 col-lg-4">
                        <h4 class="h6 text-white text-uppercase mb-3">Platform Links</h4>
                        <ul class="list-unstyled footer-links mb-0">
                            <li>
                                <a href="docs/guides/core-wordpress-sdk.md" target="_blank" rel="noopener noreferrer">
                                    Core WordPress SDK Guide
                                </a>
                            </li>
                            <li>
                                <a href="docs/plans/2025-11-11-categories-management.md" target="_blank" rel="noopener noreferrer">
                                    Categories Management Plan
                                </a>
                            </li>
                            <li>
                                <a href="docs/plans/2025-11-11-tags-management.md" target="_blank" rel="noopener noreferrer">
                                    Tags Management Plan
                                </a>
                            </li>
                            <li>
                                <a href="docs/plans/2025-11-11-posts-management.md" target="_blank" rel="noopener noreferrer">
                                    Posts Management Plan
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-12 col-lg-4">
                        <h4 class="h6 text-white text-uppercase mb-3">Support</h4>
                        <ul class="list-unstyled text-secondary small mb-0">
                            <li>
                                <span class="fw-semibold text-white">Target WordPress:</span>
                                <span class="ms-2">{{ wordpressTargetUrl }}</span>
                            </li>
                            <li>
                                <span class="fw-semibold text-white">AI Workflow:</span>
                                <span class="ms-2">docs/ai-dev-workflow-2025-en.md</span>
                            </li>
                            <li>
                                <span class="fw-semibold text-white">Support window:</span>
                                <span class="ms-2">Mon–Fri 09:00–18:00 GMT+7</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="mt-5 d-flex flex-column flex-md-row justify-content-between text-secondary small">
                    <span>© {{ currentYear }} {{ appName }}. All rights reserved.</span>
                    <span>WordPress integration target: {{ wordpressTargetUrl }}</span>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const STORAGE_KEY = 'joowp.welcome.seen';
const strapline = 'Modular Laravel • WordPress Ready • Dark Aesthetic';
const displayWelcome = ref(false);
const appName = ((import.meta.env.VITE_APP_NAME as string | undefined) ?? 'JOOwp').trim() || 'JOOwp';
const wordpressTargetUrl = 'https://soulevil.com';
const currentYear = new Date().getFullYear();

interface TaxonomyHighlight {
    name: string;
    description: string;
}

const categoryHighlights: TaxonomyHighlight[] = [
    {
        name: 'Product Releases',
        description:
            'Announce versioned drops, changelogs, and roadmap updates tied to Laravel modules or WordPress bundles.',
    },
    {
        name: 'Case Studies',
        description:
            'Long-form success narratives highlighting integrations delivered through JOOwp’s modular foundation.',
    },
    {
        name: 'Engineering Notes',
        description: 'Deep dives into architectural decisions, SDK enhancements, and operating procedures.',
    },
];

const tagHighlights: TaxonomyHighlight[] = [
    {
        name: '#dark-ui',
        description: 'Visual polish updates, theming experiments, and contrast audits across the portfolio.',
    },
    {
        name: '#automation',
        description: 'CI/CD improvements, Pint/PHPCS tuning, and workflow instrumentation.',
    },
    {
        name: '#wordpress-integration',
        description: 'API contracts, SDK revisions, and lessons from upstream WordPress deployments.',
    },
];

onMounted(() => {
    const hasSeen = window.localStorage.getItem(STORAGE_KEY);
    displayWelcome.value = !hasSeen;

    if (!hasSeen) {
        window.localStorage.setItem(STORAGE_KEY, 'true');
    }
});

const dismissWelcome = (): void => {
    displayWelcome.value = false;
};

const resetWelcome = (): void => {
    window.localStorage.removeItem(STORAGE_KEY);
    displayWelcome.value = true;
    window.localStorage.setItem(STORAGE_KEY, 'true');
};
</script>

<style scoped>
.welcome-wrapper {
    min-height: 100vh;
    background: radial-gradient(circle at top left, #202635, #0d0f16 55%);
    color: #f9fafb;
    display: flex;
    flex-direction: column;
    position: relative;
}

.content-section {
    flex: 1 0 auto;
    width: 100%;
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

.returning-card {
    background: rgba(15, 23, 42, 0.85);
    border-radius: 1.25rem;
    padding: 2rem;
}

.returning-subtitle {
    color: rgba(236, 244, 255, 0.82);
    line-height: 1.65;
    max-width: 44rem;
    letter-spacing: 0.01em;
}

.taxonomy-highlight .info-card {
    background: rgba(15, 23, 42, 0.8);
    padding: 3rem 2.5rem;
}

.info-card-alt {
    background: rgba(15, 23, 42, 0.7);
}

.feature-list {
    display: grid;
    gap: 0.75rem;
}

.feature-list-item {
    color: rgba(226, 232, 240, 0.85);
}

.taxonomy-list {
    display: grid;
    gap: 1.25rem;
}

.site-footer {
    background: rgba(15, 23, 42, 0.92);
    border-top: 1px solid rgba(148, 163, 184, 0.15);
}

.footer-links {
    display: grid;
    gap: 0.65rem;
}

.footer-links a {
    color: rgba(226, 232, 240, 0.85);
    text-decoration: none;
    transition: color 0.2s ease;
}

.footer-links a:hover,
.footer-links a:focus {
    color: #60a5fa;
}

@media (max-width: 991.98px) {
    .taxonomy-highlight .info-card,
    .taxonomy-highlight .info-card-alt {
        border-radius: 0;
    }
}
</style>
