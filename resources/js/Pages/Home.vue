<template>
    <div class="container py-5">
        <header class="text-center mb-5">
            <h1 class="display-5 fw-semibold mb-3">
                {{ title }}
            </h1>
            <p class="lead text-muted mb-0">
                Build modular Laravel applications with Vue, Bootstrap, and Font Awesome out of the box.
            </p>
        </header>

        <section class="row g-4">
            <article class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3 text-primary">
                            <span class="fa-solid fa-layer-group fa-2x"></span>
                            <h2 class="h5 mb-0">Domain Modules</h2>
                        </div>
                        <p class="card-text text-secondary">
                            Encapsulate business areas with <code>nwidart/laravel-modules</code>. Keep cross-cutting abstractions
                            inside the Core module and inject contracts everywhere else.
                        </p>
                    </div>
                </div>
            </article>

            <article class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3 text-success">
                            <span class="fa-solid fa-check-double fa-2x"></span>
                            <h2 class="h5 mb-0">Quality Pipeline</h2>
                        </div>
                        <p class="card-text text-secondary">
                            Run <code>composer lint</code> before every commit to execute Pint, PHPCS, PHPMD, and PHPStan in the
                            enforced order. Keep suppressions rare and documented.
                        </p>
                    </div>
                </div>
            </article>

            <article class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3 text-warning">
                            <span class="fa-solid fa-vial-circle-check fa-2x"></span>
                            <h2 class="h5 mb-0">Integration Tests</h2>
                        </div>
                        <p class="card-text text-secondary">
                            Each module must ship an end-to-end “golden path” test. Favor Laravel testing helpers and stub external
                            integrations only when absolutely necessary.
                        </p>
                    </div>
                </div>
            </article>
        </section>

        <footer class="mt-5 text-center">
            <a
                class="btn btn-primary btn-lg shadow-sm px-4"
                href="https://laravel.com/docs"
                target="_blank"
                rel="noopener noreferrer"
            >
                <span class="fa-solid fa-compass me-2"></span>
                Explore Laravel Docs
            </a>
        </footer>
    </div>
</template>

<script setup lang="ts">
const title: string = 'JOOservices Platform Starter';
</script>

