<template>
    <div class="tags-page container-fluid py-5 text-white">
        <header class="mb-5">
            <h1 class="display-6 mb-2">Tags Management</h1>
            <p class="text-secondary mb-0 small">
                Manage WordPress tags, organize content with flat taxonomy
            </p>
        </header>

        <div v-if="!tokenStatus.remembered" class="alert alert-danger border border-danger-subtle mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                    <strong class="me-2">WordPress authentication required.</strong>
                    Please login on home page first before using this feature.
                    </div>
                <a href="/" class="btn btn-sm btn-outline-light">Go to Home</a>
            </div>
        </div>

        <div class="row gy-4">
            <div v-if="tokenStatus.remembered" class="col-12 col-xl-8">
                <section class="card bg-dark border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                            <h2 class="h5 text-white mb-0">Tags</h2>
                            <div class="d-flex gap-2 align-items-center mt-2 mt-md-0 flex-nowrap">
                                <label class="text-secondary small mb-0 text-nowrap">Per page</label>
                                <select
                                    v-model="filters.perPage"
                                    class="form-select form-select-sm bg-transparent text-white border-secondary-subtle"
                                    :disabled="!tokenStatus.remembered"
                                >
                                    <option v-for="option in perPageOptions" :key="option" :value="option">
                                        {{ option === 'all' ? 'Show all' : option }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="toolbar d-flex flex-column flex-md-row gap-3 align-items-md-center mb-4">
                            <div class="input-group search-group">
                                <span class="input-group-text bg-transparent text-secondary border-secondary-subtle">
                                    <span class="fa-solid fa-magnifying-glass"></span>
                                </span>
                                <input
                                    v-model="filters.search"
                                    type="search"
                                    class="form-control bg-transparent border-secondary-subtle text-white"
                                    placeholder="Search tags"
                                    :disabled="!tokenStatus.remembered"
                                    @input="debouncedSearch"
                                />
                            </div>
                            <div class="d-flex gap-2 align-items-center ms-md-auto">
                                <button
                                    type="button"
                                    class="btn btn-tertiary btn-sm"
                                    :disabled="isLoading || !tokenStatus.remembered"
                                    @click="fetchTags"
                                >
                                    <span v-if="!isLoading" class="fa-solid fa-arrows-rotate"></span>
                                    <span v-else class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    <span class="ms-1">Refresh</span>
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-dark table-hover align-middle text-white">
                                <thead>
                                    <tr class="text-secondary">
                                        <th scope="col" class="sortable" @click="cycleSort('id')">
                                            <span class="sortable-inner">
                                                <span>ID</span>
                                                <i :class="sortIcon('id')" aria-hidden="true"></i>
                                            </span>
                                        </th>
                                        <th scope="col" class="sortable" @click="cycleSort('name')">
                                            <span class="sortable-inner">
                                                <span>Name</span>
                                                <i :class="sortIcon('name')" aria-hidden="true"></i>
                                            </span>
                                        </th>
                                        <th scope="col" class="sortable" @click="cycleSort('slug')">
                                            <span class="sortable-inner">
                                                <span>Slug</span>
                                                <i :class="sortIcon('slug')" aria-hidden="true"></i>
                                            </span>
                                        </th>
                                        <th scope="col" class="sortable" @click="cycleSort('posts')">
                                            <span class="sortable-inner">
                                                <span>Posts</span>
                                                <i :class="sortIcon('posts')" aria-hidden="true"></i>
                                            </span>
                                        </th>
                                        <th scope="col" class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="isLoading">
                                        <td colspan="5" class="text-center">
                                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                            Loading tags…
                                        </td>
                                    </tr>
                                    <tr v-else-if="tags.length === 0">
                                        <td colspan="5" class="text-center text-secondary">
                                            No tags match the current filters.
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="tag in displayTags"
                                        :key="tag.id"
                                        class="table-row"
                                        @click="selectForEdit(tag)"
                                    >
                                        <td>
                                            <span class="fw-semibold">{{ tag.id }}</span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">
                                                {{ tag.name }}
                                            </div>
                                            <div class="text-secondary small" v-html="sanitizedDescription(tag.description)"></div>
                                        </td>
                                        <td>{{ tag.slug }}</td>
                                        <td>
                                            <span class="fw-semibold">{{ tag.count }}</span>
                                        </td>
                                        <td class="text-end">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-danger d-inline-flex align-items-center justify-content-center gap-2 px-3"
                                                :disabled="isSubmitting || !tokenStatus.remembered"
                                                @click.stop="confirmDelete(tag)"
                                            >
                                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                                <span class="visually-hidden">Delete</span>
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
                                :disabled="filters.page === 1 || isLoading || !tokenStatus.remembered"
                                @click="changePage(-1)"
                            >
                                Previous
                            </button>
                            <span class="text-secondary small">
                                Page {{ filters.page }} · showing {{ tags.length }} results
                            </span>
                            <button
                                type="button"
                                class="btn btn-outline-light btn-sm"
                                :disabled="(filters.perPage !== 'all' && typeof filters.perPage === 'number' && tags.length < filters.perPage) || isLoading || !tokenStatus.remembered"
                                @click="changePage(1)"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            <div v-if="tokenStatus.remembered" class="col-12 col-xl-4">
                <section class="card bg-dark border-0 shadow-sm sticky-form">
                    <div class="card-body">
                        <h2 class="h5 text-white mb-3">
                            {{ editingTag ? 'Update tag' : 'Create tag' }}
                        </h2>
                        <p class="text-secondary small mb-4">
                            {{ editingTag ? 'Editing #' + editingTag.id : 'New taxonomy record' }} —
                            aligned with remember-token enforcement.
                        </p>
                        <form class="d-flex flex-column gap-3" @submit.prevent="submitTag">
                            <div>
                                <label for="tag-name" class="form-label text-secondary small">Name</label>
                                <input
                                    id="tag-name"
                                    v-model="form.name"
                                    type="text"
                                    class="form-control bg-transparent border-secondary-subtle text-white"
                                    placeholder="e.g. Technology"
                                    :disabled="!tokenStatus.remembered"
                                    required
                                />
                            </div>
                            <div>
                                <label for="tag-slug" class="form-label text-secondary small">Slug</label>
                                <input
                                    id="tag-slug"
                                    v-model="form.slug"
                                    type="text"
                                    class="form-control bg-transparent border-secondary-subtle text-white"
                                    placeholder="technology"
                                    :disabled="!tokenStatus.remembered"
                                    @input="handleSlugInput"
                                />
                            </div>
                            <div>
                                <label for="tag-description" class="form-label text-secondary small">Description</label>
                                <QuillEditor
                                    id="tag-description"
                                    v-model:content="form.description"
                                    content-type="html"
                                    :toolbar="descriptionToolbar"
                                    theme="snow"
                                    :disabled="!tokenStatus.remembered"
                                    placeholder="Optional summary for editors and SEO."
                                    class="quill-editor-dark"
                                />
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-fill d-flex align-items-center justify-content-center gap-2" :disabled="isSubmitting || !tokenStatus.remembered">
                                    <span v-if="isSubmitting" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    <span v-else class="fa-solid" :class="editingTag ? 'fa-floppy-disk' : 'fa-plus-circle'"></span>
                                    <span>{{ editingTag ? 'Update tag' : 'Create tag' }}</span>
                                </button>
                                <button
                                    v-if="editingTag"
                                    type="button"
                                    class="btn btn-outline-light"
                                    :disabled="isSubmitting || !tokenStatus.remembered"
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
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

defineOptions({ layout: AppLayout });

interface Tag {
    id: number;
    name: string;
    slug: string;
    description: string;
    count: number;
}

interface TokenStatus {
    remembered: boolean;
    username?: string | null;
}

type SortColumn = 'id' | 'name' | 'slug' | 'posts';
type SortDirection = 'asc' | 'desc';

const tags = ref<Tag[]>([]);
const isLoading = ref(false);
const isSubmitting = ref(false);
const editingTag = ref<Tag | null>(null);
const tokenStatus = ref<TokenStatus>({ remembered: false, username: null });
const alerts = ref<Array<{ id: string; variant: 'success' | 'danger'; message: string }>>([]);
const sortState = reactive<{ column: SortColumn; direction: SortDirection }>({ column: 'name', direction: 'asc' });

// Quill editor toolbar configuration (minimal: Bold, Italic, Link, Lists)
const descriptionToolbar = [
    ['bold', 'italic'],
    ['link'],
    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
];

const displayTags = computed<Tag[]>(() => {
    if (sortState.column === 'name' && sortState.direction === 'asc') {
        return [...tags.value].sort((a, b) => a.name.localeCompare(b.name));
    }

    return [...tags.value].sort(compareTags);
});

const filters = reactive({
    search: '',
    perPage: 10 as number | 'all',
    page: 1,
});

const perPageOptions = [10, 20, 50, 100, 'all'] as const;

const form = reactive({
    name: '',
    slug: '',
    description: '',
});

// Track if slug was manually edited to prevent auto-generation override
const slugManuallyEdited = ref(false);

let searchTimeout: number | undefined;

/**
 * Generate URL-friendly slug from name
 * - Convert to lowercase
 * - Replace spaces and special characters with hyphens
 * - Remove multiple consecutive hyphens
 * - Trim hyphens from start and end
 */
const generateSlug = (name: string): string => {
    if (!name) {
        return '';
    }

    return name
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '') // Remove special characters except word chars, spaces, hyphens
        .replace(/[\s_]+/g, '-') // Replace spaces and underscores with hyphens
        .replace(/-+/g, '-') // Replace multiple hyphens with single hyphen
        .replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens
};

const fetchTags = async (): Promise<void> => {
    if (!tokenStatus.value.remembered) {
        tags.value = [];
        return;
    }

    isLoading.value = true;
    try {
        const params: Record<string, string | number | boolean | undefined> = {
            search: filters.search || undefined,
            page: filters.page,
        };

        // Only include per_page if not "all"
        if (filters.perPage !== 'all') {
            params.per_page = filters.perPage;
        }

        const response = await window.axios.get('/api/v1/wordpress/tags', {
            params,
        });

        // Handle structured error responses from API
        if (response.data?.ok === false) {
            pushAlert('danger', response.data.message || 'Unable to retrieve tags from WordPress.');
            tags.value = [];
            return;
        }

        tags.value = response.data?.data?.items ?? [];
    } catch (error: unknown) {
        pushAlert('danger', extractErrorMessage(error));
        tags.value = [];
    } finally {
        isLoading.value = false;
    }
};

const submitTag = async (): Promise<void> => {
    if (!tokenStatus.value.remembered) {
        return; // Alert already shown at top of page
    }

    isSubmitting.value = true;
    const payload = {
        name: form.name,
        slug: form.slug || undefined,
        description: form.description || undefined,
    };

    try {
        let response;
        if (editingTag.value) {
            response = await window.axios.post(`/api/v1/wordpress/tags/${editingTag.value.id}`, payload);
        } else {
            response = await window.axios.post('/api/v1/wordpress/tags', payload);
        }

        // Handle structured error responses from API
        if (response.data?.ok === false) {
            pushAlert('danger', response.data.message || 'Unable to save tag in WordPress.');
            return;
        }

        pushAlert('success', editingTag.value ? 'Tag updated.' : 'Tag created.');
        await fetchTags();
        resetForm();
    } catch (error: unknown) {
        pushAlert('danger', extractErrorMessage(error));
    } finally {
        isSubmitting.value = false;
    }
};

const confirmDelete = async (tag: Tag): Promise<void> => {
    if (!tokenStatus.value.remembered) {
        return; // Alert already shown at top of page
    }

    if (! window.confirm(`Delete "${tag.name}"? This bypasses the trash and is irreversible.`)) {
        return;
    }

    isSubmitting.value = true;
    try {
        const response = await window.axios.delete(`/api/v1/wordpress/tags/${tag.id}`, {
            data: {
                force: true,
            },
        });

        // Handle structured error responses from API
        if (response.data?.ok === false) {
            pushAlert('danger', response.data.message || 'Unable to delete tag from WordPress.');
            return;
        }

        pushAlert('success', 'Tag deleted.');
        if (editingTag.value?.id === tag.id) {
            resetForm();
        }
        await fetchTags();
    } catch (error: unknown) {
        pushAlert('danger', extractErrorMessage(error));
    } finally {
        isSubmitting.value = false;
    }
};

const selectForEdit = (tag: Tag): void => {
    editingTag.value = tag;
    form.name = tag.name;
    form.slug = tag.slug;
    form.description = tag.description;
    slugManuallyEdited.value = !!tag.slug; // If tag has slug, consider it manually set
};

const resetForm = (): void => {
    editingTag.value = null;
    form.name = '';
    form.slug = '';
    form.description = '';
    slugManuallyEdited.value = false;
};

// Track manual slug edits
const handleSlugInput = (): void => {
    // If user clears slug, allow auto-generation again
    if (!form.slug || form.slug.trim() === '') {
        slugManuallyEdited.value = false;
    } else {
        slugManuallyEdited.value = true;
    }
};

// Auto-generate slug from name when name changes and slug is empty
watch(() => form.name, (newName) => {
    // Only auto-generate if slug is empty and was not manually edited
    if (!slugManuallyEdited.value && (!form.slug || form.slug.trim() === '')) {
        form.slug = generateSlug(newName);
    }
});

const debouncedSearch = (): void => {
    window.clearTimeout(searchTimeout);
    searchTimeout = window.setTimeout(() => {
        filters.page = 1;
        void fetchTags();
    }, 450);
};

const changePage = (direction: 1 | -1): void => {
    const nextPage = filters.page + direction;
    if (nextPage < 1) {
        return;
    }
    filters.page = nextPage;
    void fetchTags();
};

const compareTags = (first: Tag, second: Tag): number => {
    const directionMultiplier = sortState.direction === 'asc' ? 1 : -1;

    switch (sortState.column) {
        case 'id':
            return (first.id - second.id) * directionMultiplier;
        case 'slug':
            return first.slug.localeCompare(second.slug) * directionMultiplier;
        case 'posts':
            return (first.count - second.count) * directionMultiplier;
        case 'name':
        default:
            return first.name.localeCompare(second.name) * directionMultiplier;
    }
};

const cycleSort = (column: SortColumn): void => {
    if (sortState.column === column) {
        if (sortState.direction === 'asc') {
            sortState.direction = 'desc';
        } else {
            sortState.column = 'name';
            sortState.direction = 'asc';
        }
    } else {
        sortState.column = column;
        sortState.direction = 'asc';
    }
};

const sortIcon = (column: SortColumn): string => {
    if (sortState.column !== column) {
        return 'fa-solid fa-sort text-secondary opacity-50';
    }

    return sortState.direction === 'asc'
        ? 'fa-solid fa-sort-up text-info'
        : 'fa-solid fa-sort-down text-info';
};

const sanitizedDescription = (rawDescription: string): string => {
    if (! rawDescription) {
        return '<span class="text-secondary">No description</span>';
    }

    const textParser = document.createElement('div');
    textParser.innerHTML = rawDescription;

    const allowedTags = new Set(['STRONG', 'EM', 'B', 'I', 'U', 'A', 'BR', 'P', 'UL', 'OL', 'LI', 'SPAN', 'SMALL']);

    const sanitizeNode = (node: Node): string => {
        if (node.nodeType === Node.TEXT_NODE) {
            return (node.textContent ?? '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        if (node.nodeType === Node.ELEMENT_NODE) {
            const element = node as HTMLElement;
            if (! allowedTags.has(element.tagName)) {
                return sanitizeNode(document.createTextNode(element.textContent ?? ''));
            }

            const sanitizedChildren = Array.from(element.childNodes).map((child) => sanitizeNode(child)).join('');

            const attributeString = element.tagName === 'A' && element.getAttribute('href')
                ? ` href="${element.getAttribute('href')}" rel="noopener noreferrer" target="_blank"`
                : '';

            return `<${element.tagName.toLowerCase()}${attributeString}>${sanitizedChildren}</${element.tagName.toLowerCase()}>`;
        }

        return '';
    };

    return Array.from(textParser.childNodes).map((child) => sanitizeNode(child)).join('');
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
        void fetchTags();
    }
);

onMounted(async () => {
    await refreshTokenStatus();
    if (tokenStatus.value.remembered) {
        await fetchTags();
    }
});

watch(
    () => tokenStatus.value.remembered,
    (remembered) => {
        if (remembered) {
            void fetchTags();
        } else {
            tags.value = [];
        }
    }
);
</script>

<style scoped>
.tags-page {
    min-height: calc(100vh - 6rem);
    background: linear-gradient(180deg, #1a2234 0%, #101726 55%, #0d1421 100%);
}

/* Quill Editor Dark Theme Styles */
:deep(.quill-editor-dark .ql-toolbar) {
    background: rgba(39, 53, 76, 0.8);
    border-color: rgba(78, 99, 135, 0.5);
    border-radius: 0.375rem 0.375rem 0 0;
}

:deep(.quill-editor-dark .ql-toolbar .ql-stroke) {
    stroke: rgba(159, 174, 203, 0.8);
}

:deep(.quill-editor-dark .ql-toolbar .ql-fill) {
    fill: rgba(159, 174, 203, 0.8);
}

:deep(.quill-editor-dark .ql-toolbar button:hover .ql-stroke),
:deep(.quill-editor-dark .ql-toolbar button.ql-active .ql-stroke) {
    stroke: rgba(240, 245, 252, 0.96);
}

:deep(.quill-editor-dark .ql-toolbar button:hover .ql-fill),
:deep(.quill-editor-dark .ql-toolbar button.ql-active .ql-fill) {
    fill: rgba(240, 245, 252, 0.96);
}

:deep(.quill-editor-dark .ql-container) {
    background: transparent;
    border-color: rgba(78, 99, 135, 0.5);
    border-radius: 0 0 0.375rem 0.375rem;
    color: rgba(240, 245, 252, 0.96);
    font-family: inherit;
}

:deep(.quill-editor-dark .ql-editor) {
    color: rgba(240, 245, 252, 0.96);
    min-height: 100px;
}

:deep(.quill-editor-dark .ql-editor.ql-blank::before) {
    color: rgba(159, 174, 203, 0.6);
    font-style: normal;
}

:deep(.quill-editor-dark .ql-editor a) {
    color: #0d6efd;
}

:deep(.quill-editor-dark .ql-editor.ql-disabled) {
    opacity: 0.6;
    cursor: not-allowed;
}

.tags-page .card {
    background: #1f2a3c !important;
    border: 1px solid rgba(58, 72, 99, 0.6) !important;
    box-shadow: 0 18px 35px rgba(10, 13, 22, 0.45);
}

.tags-page header p,
.tags-page header h1,
.tags-page header strong {
    color: rgba(241, 244, 251, 0.96);
}

.toolbar .form-control:focus,
.toolbar .form-select:focus,
.toolbar .form-control,
.toolbar .form-select {
    color: rgba(240, 245, 252, 0.96);
    background: rgba(39, 53, 76, 0.8);
    border-color: rgba(78, 99, 135, 0.5);
}

.toolbar .form-select {
    color: rgba(159, 174, 203, 0.8);
}

.table thead th.sortable {
    cursor: pointer;
    user-select: none;
    font-weight: 600;
    color: rgba(148, 163, 184, 0.95);
}

.table thead th.sortable:hover,
.table thead th.sortable:focus {
    color: rgba(226, 232, 240, 0.95);
}

.sortable-inner {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.btn-tertiary {
    background: rgba(41, 55, 78, 0.85);
    border: 1px solid rgba(78, 99, 135, 0.55);
    color: rgba(221, 229, 248, 0.95);
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding-inline: 0.9rem;
    box-shadow: 0 8px 18px rgba(10, 13, 22, 0.25);
}

.btn-tertiary:hover,
.btn-tertiary:focus {
    background: rgba(68, 85, 120, 0.92);
    border-color: rgba(121, 144, 184, 0.65);
    color: rgba(246, 248, 255, 0.98);
}

.tags-page .form-control::placeholder,
.tags-page .form-select option {
    color: rgba(159, 174, 203, 0.8);
}

.table-row:hover {
    cursor: pointer;
    background: rgba(59, 130, 246, 0.18);
}

.tags-page .btn-outline-light {
    color: #8faadc;
    border-color: rgba(143, 170, 220, 0.45);
}

.tags-page .btn-outline-light:hover,
.tags-page .btn-outline-light:focus {
    color: #0f172a;
    background: linear-gradient(135deg, #8faadc, #a5b8e6);
    border-color: #8faadc;
}

.tags-page .btn-primary {
    background: linear-gradient(135deg, #4e73df, #2e59d9);
    border: none;
    box-shadow: 0 10px 25px rgba(46, 89, 217, 0.35);
}

.tags-page .btn-primary:disabled,
.tags-page .btn-primary:focus,
.tags-page .btn-primary:hover {
    background: linear-gradient(135deg, #5a82ef, #3b6bea);
    border: none;
}

.tags-page .table-dark {
    --bs-table-bg: rgba(16, 23, 35, 0.92);
    --bs-table-striped-bg: rgba(28, 39, 61, 0.92);
    --bs-table-hover-bg: rgba(46, 89, 217, 0.18);
    --bs-table-color: rgba(238, 242, 255, 0.95);
}

.tags-page .table-dark thead tr {
    background: linear-gradient(135deg, rgba(46, 89, 217, 0.28), rgba(59, 130, 246, 0.18));
}

.tags-page .table-dark tbody tr {
    border-color: rgba(54, 65, 86, 0.55);
}

.tags-page .table-dark tbody tr td:first-child .fw-semibold {
    color: rgba(247, 249, 255, 0.98);
}

.tags-page .table-dark tbody tr td:first-child .text-secondary {
    color: rgba(177, 192, 219, 0.92) !important;
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

.tags-page .input-group-text {
    background: rgba(34, 48, 71, 0.9);
    border-color: rgba(68, 85, 120, 0.55);
    color: rgba(188, 202, 230, 0.92);
}
</style>
