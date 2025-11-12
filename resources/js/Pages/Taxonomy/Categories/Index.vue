<template>
    <div class="categories-page container py-5 text-white">
        <header class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-5 gap-3">
            <div>
                <p class="text-uppercase text-secondary mb-2 small">Taxonomy Suite</p>
                <h1 class="display-6 mb-2">Categories Management</h1>
                <p class="text-secondary mb-0">
                    Search, audit, and mutate WordPress categories without leaving JOOwp. Every action streams through the WordPress SDK,
                    ActionLogger, and remember-token workflow defined in the 2025-11-11 plan.
                </p>
            </div>
            <a
                class="btn btn-outline-light btn-sm"
                href="/docs/plans/2025-11-11-categories-management.md"
                target="_blank"
                rel="noopener noreferrer"
            >
                View Plan
            </a>
        </header>

        <div v-if="!tokenStatus.remembered" class="alert alert-warning border border-warning-subtle text-dark mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <strong class="me-2">No remembered WordPress token.</strong>
                    Store a token on the home page before issuing mutations.
                </div>
                <button type="button" class="btn btn-sm btn-outline-dark" @click="refreshTokenStatus">
                    Refresh status
                </button>
            </div>
        </div>

        <div class="row gy-4">
            <div class="col-12 col-xl-8">
                <section class="card bg-dark border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="toolbar d-flex flex-column flex-md-row gap-3 align-items-md-center mb-4">
                            <div class="input-group search-group">
                                <span class="input-group-text bg-transparent text-secondary border-secondary-subtle">
                                    <span class="fa-solid fa-magnifying-glass"></span>
                                </span>
                                <input
                                    v-model="filters.search"
                                    type="search"
                                    class="form-control bg-transparent border-secondary-subtle text-white"
                                    placeholder="Search categories"
                                    @input="debouncedSearch"
                                />
                            </div>
                            <div class="d-flex gap-2 align-items-center ms-md-auto">
                                <label class="text-secondary small mb-0">Per page</label>
                                <select
                                    v-model.number="filters.perPage"
                                    class="form-select form-select-sm bg-transparent text-white border-secondary-subtle"
                                >
                                    <option v-for="option in perPageOptions" :key="option" :value="option">
                                        {{ option }}
                                    </option>
                                </select>
                                <button
                                    type="button"
                                    class="btn btn-outline-light btn-sm"
                                    :disabled="isLoading"
                                    @click="fetchCategories"
                                >
                                    <span v-if="!isLoading" class="fa-solid fa-arrows-rotate me-2"></span>
                                    <span v-else class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    Refresh
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-dark table-hover align-middle text-white">
                                <thead>
                                    <tr class="text-secondary">
                                        <th scope="col">Name</th>
                                        <th scope="col">Slug</th>
                                        <th scope="col">Parent</th>
                                        <th scope="col">Entries</th>
                                        <th scope="col" class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="isLoading">
                                        <td colspan="5" class="text-center">
                                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                            Loading categories…
                                        </td>
                                    </tr>
                                    <tr v-else-if="categories.length === 0">
                                        <td colspan="5" class="text-center text-secondary">
                                            No categories match the current filters.
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="category in categories"
                                        :key="category.id"
                                        class="table-row"
                                        @click="selectForEdit(category)"
                                    >
                                        <td>
                                            <div class="fw-semibold">{{ category.name }}</div>
                                            <div class="text-secondary small">{{ truncate(category.description) }}</div>
                                        </td>
                                        <td>{{ category.slug }}</td>
                                        <td>
                                            <span v-if="category.parent === 0" class="badge text-bg-secondary rounded-pill">Root</span>
                                            <span v-else class="badge text-bg-dark border border-secondary rounded-pill">
                                                #{{ category.parent }}
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                class="status-chip"
                                                :class="category.count > 0 ? 'chip-live' : 'chip-empty'"
                                            >
                                                {{ category.count > 0 ? 'Active' : 'Empty' }}
                                                <small class="ms-2 text-secondary">{{ category.count }}</small>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-info me-2"
                                                @click.stop="selectForEdit(category)"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                :disabled="isSubmitting"
                                                @click.stop="confirmDelete(category)"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-3">
                            <button
                                type="button"
                                class="btn btn-outline-light btn-sm"
                                :disabled="filters.page === 1 || isLoading"
                                @click="changePage(-1)"
                            >
                                Previous
                            </button>
                            <span class="text-secondary small">
                                Page {{ filters.page }} · showing {{ categories.length }} results
                            </span>
                            <button
                                type="button"
                                class="btn btn-outline-light btn-sm"
                                :disabled="categories.length < filters.perPage || isLoading"
                                @click="changePage(1)"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-4">
                <section class="card bg-dark border-0 shadow-sm sticky-form">
                    <div class="card-body">
                        <h2 class="h5 text-white mb-3">
                            {{ editingCategory ? 'Update category' : 'Create category' }}
                        </h2>
                        <p class="text-secondary small mb-4">
                            {{ editingCategory ? 'Editing #' + editingCategory.id : 'New taxonomy record' }} —
                            aligned with remember-token enforcement.
                        </p>
                        <form class="d-flex flex-column gap-3" @submit.prevent="submitCategory">
                            <div>
                                <label for="category-name" class="form-label text-secondary small">Name</label>
                                <input
                                    id="category-name"
                                    v-model="form.name"
                                    type="text"
                                    class="form-control bg-transparent border-secondary-subtle text-white"
                                    placeholder="e.g. Product Releases"
                                    required
                                />
                            </div>
                            <div>
                                <label for="category-slug" class="form-label text-secondary small">Slug</label>
                                <input
                                    id="category-slug"
                                    v-model="form.slug"
                                    type="text"
                                    class="form-control bg-transparent border-secondary-subtle text-white"
                                    placeholder="product-releases"
                                />
                            </div>
                            <div>
                                <label for="category-description" class="form-label text-secondary small">Description</label>
                                <textarea
                                    id="category-description"
                                    v-model="form.description"
                                    class="form-control bg-transparent border-secondary-subtle text-white"
                                    rows="3"
                                    placeholder="Optional summary for editors and SEO."
                                ></textarea>
                            </div>
                            <div>
                                <label for="category-parent" class="form-label text-secondary small">Parent ID</label>
                                <input
                                    id="category-parent"
                                    v-model.number="form.parent"
                                    type="number"
                                    min="0"
                                    class="form-control bg-transparent border-secondary-subtle text-white"
                                    placeholder="0 (root)"
                                />
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-fill" :disabled="isSubmitting">
                                    <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    {{ editingCategory ? 'Update category' : 'Create category' }}
                                </button>
                                <button
                                    v-if="editingCategory"
                                    type="button"
                                    class="btn btn-outline-light"
                                    :disabled="isSubmitting"
                                    @click="resetForm"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </div>

        <transition-group name="alert" tag="div" class="alert-stack">
            <div
                v-for="alert in alerts"
                :key="alert.id"
                class="alert"
                :class="alert.variant === 'success' ? 'alert-success border-0' : 'alert-danger border-0'"
            >
                {{ alert.message }}
            </div>
        </transition-group>
    </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue';

interface Category {
    id: number;
    name: string;
    slug: string;
    description: string;
    parent: number;
    count: number;
}

interface TokenStatus {
    remembered: boolean;
    username?: string | null;
}

const categories = ref<Category[]>([]);
const isLoading = ref(false);
const isSubmitting = ref(false);
const editingCategory = ref<Category | null>(null);
const tokenStatus = ref<TokenStatus>({ remembered: false, username: null });
const alerts = ref<Array<{ id: string; variant: 'success' | 'danger'; message: string }>>([]);
const perPageOptions = [10, 20, 40, 80];

const filters = reactive({
    search: '',
    perPage: 10,
    page: 1,
});

const form = reactive({
    name: '',
    slug: '',
    description: '',
    parent: 0 as number | null,
});

let searchTimeout: number | undefined;

const fetchCategories = async (): Promise<void> => {
    isLoading.value = true;
    try {
        const response = await window.axios.get('/api/v1/wordpress/categories', {
            params: {
                search: filters.search || undefined,
                per_page: filters.perPage,
                page: filters.page,
            },
        });

        categories.value = response.data?.data?.items ?? [];
    } catch (error: unknown) {
        pushAlert('danger', extractErrorMessage(error));
    } finally {
        isLoading.value = false;
    }
};

const submitCategory = async (): Promise<void> => {
    isSubmitting.value = true;
    const payload = {
        name: form.name,
        slug: form.slug || undefined,
        description: form.description || undefined,
        parent: form.parent ?? 0,
    };

    try {
        if (editingCategory.value) {
            await window.axios.post(`/api/v1/wordpress/categories/${editingCategory.value.id}`, payload);
            pushAlert('success', 'Category updated.');
        } else {
            await window.axios.post('/api/v1/wordpress/categories', payload);
            pushAlert('success', 'Category created.');
        }

        await fetchCategories();
        resetForm();
    } catch (error: unknown) {
        pushAlert('danger', extractErrorMessage(error));
    } finally {
        isSubmitting.value = false;
    }
};

const confirmDelete = async (category: Category): Promise<void> => {
    if (! window.confirm(`Delete “${category.name}”? This bypasses the trash and is irreversible.`)) {
        return;
    }

    isSubmitting.value = true;
    try {
        await window.axios.delete(`/api/v1/wordpress/categories/${category.id}`, {
            data: {
                force: true,
            },
        });
        pushAlert('success', 'Category deleted.');
        if (editingCategory.value?.id === category.id) {
            resetForm();
        }
        await fetchCategories();
    } catch (error: unknown) {
        pushAlert('danger', extractErrorMessage(error));
    } finally {
        isSubmitting.value = false;
    }
};

const selectForEdit = (category: Category): void => {
    editingCategory.value = category;
    form.name = category.name;
    form.slug = category.slug;
    form.description = category.description;
    form.parent = category.parent;
};

const resetForm = (): void => {
    editingCategory.value = null;
    form.name = '';
    form.slug = '';
    form.description = '';
    form.parent = 0;
};

const debouncedSearch = (): void => {
    window.clearTimeout(searchTimeout);
    searchTimeout = window.setTimeout(() => {
        filters.page = 1;
        void fetchCategories();
    }, 450);
};

const changePage = (direction: 1 | -1): void => {
    const nextPage = filters.page + direction;
    if (nextPage < 1) {
        return;
    }
    filters.page = nextPage;
    void fetchCategories();
};

const truncate = (text: string, length = 80): string => {
    if (! text) {
        return 'No description';
    }
    return text.length > length ? `${text.substring(0, length)}…` : text;
};

const pushAlert = (variant: 'success' | 'danger', message: string): void => {
    const id = window.crypto.randomUUID?.() ?? `${Date.now()}-${Math.random().toString(16).slice(2)}`;
    alerts.value.push({ id, variant, message });
    window.setTimeout(() => {
        alerts.value = alerts.value.filter((alert) => alert.id !== id);
    }, 4000);
};

const refreshTokenStatus = async (): Promise<void> => {
    try {
        const response = await window.axios.get('/api/v1/wordpress/token');
        tokenStatus.value = response.data?.data ?? { remembered: false };
    } catch (error: unknown) {
        pushAlert('danger', extractErrorMessage(error));
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

watch(
    () => filters.perPage,
    () => {
        filters.page = 1;
        void fetchCategories();
    }
);

onMounted(async () => {
    await Promise.all([refreshTokenStatus(), fetchCategories()]);
});
</script>

<style scoped>
.categories-page {
    min-height: calc(100vh - 6rem);
}

.toolbar .form-control:focus,
.toolbar .form-select:focus,
.toolbar .form-control,
.toolbar .form-select {
    color: #f8fafc;
}

.table-row:hover {
    cursor: pointer;
    background: rgba(96, 165, 250, 0.08);
}

.status-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    border-radius: 999px;
    padding: 0.2rem 0.75rem;
    font-size: 0.85rem;
}

.chip-live {
    background: rgba(34, 197, 94, 0.15);
    color: #86efac;
}

.chip-empty {
    background: rgba(148, 163, 184, 0.15);
    color: rgba(226, 232, 240, 0.8);
}

.sticky-form {
    position: sticky;
    top: 1.5rem;
}

.alert-stack {
    position: fixed;
    top: 1.5rem;
    right: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    z-index: 1200;
    width: min(320px, 90vw);
}

.alert-enter-active,
.alert-leave-active {
    transition: all 0.2s ease;
}

.alert-enter-from,
.alert-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
