<template>
    <div class="taxonomy-page container py-5 text-white">
        <div class="row gy-4">
            <div class="col-12">
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="link-light text-decoration-none" href="/">Home</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Categories</li>
                    </ol>
                </nav>
                <header class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
                    <div>
                        <h1 class="h3 text-white mb-2">
                            <span class="fa-solid fa-layer-group me-2 text-primary"></span>
                            Categories Management
                        </h1>
                        <p class="text-secondary mb-0">
                            Centralise WordPress categories, enforce naming standards, and launch taxonomy-driven experiments.
                        </p>
                    </div>
                    <a class="btn btn-outline-light btn-sm mt-3 mt-md-0" href="/docs/plans/2025-11-11-categories-management.md" target="_blank" rel="noopener noreferrer">
                        View Plan
                    </a>
                </header>
            </div>
            <div class="col-12 col-xl-8">
                <section class="card bg-dark border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h4 text-white mb-3">Work in progress</h2>
                        <p class="text-secondary">
                            Categories management is currently under development. Follow the plan file for milestones, API contracts, and UI specifications.
                            Once the feature reaches the prototype stage, this page will load the full Vue/Inertia workflow.
                        </p>
                        <ul class="text-secondary small">
                            <li>API design and FormRequest validation.</li>
                            <li>Shared taxonomy UI components (table, search, pagination).</li>
                            <li>Audit logging and remember-token safeguards for all mutations.</li>
                        </ul>
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-4">
                <aside class="card bg-dark border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h3 class="h5 text-white mb-3">Next steps</h3>
                        <ol class="list-group list-group-numbered">
                            <li class="list-group-item bg-dark text-secondary">Confirm field requirements with product owners.</li>
                            <li class="list-group-item bg-dark text-secondary">Expose read/write endpoints via the Core module.</li>
                            <li class="list-group-item bg-dark text-secondary">Ship UI prototype leveraging the shared taxonomy components.</li>
                        </ol>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
</script>

<style scoped>
.taxonomy-page {
    min-height: calc(100vh - 6rem);
}

.breadcrumb-item + .breadcrumb-item::before {
    color: rgba(148, 163, 184, 0.6);
}

.list-group-item {
    border-color: rgba(148, 163, 184, 0.2);
}
</style>

