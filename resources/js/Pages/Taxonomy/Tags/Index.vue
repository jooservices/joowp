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
                <a href="/" class="btn btn-sm btn-outline-light d-inline-flex align-items-center gap-2">
                    <i class="fa-solid fa-house" aria-hidden="true"></i>
                    <span>Go to Home</span>
                </a>
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
                                    class="btn btn-tertiary btn-sm d-inline-flex align-items-center gap-2"
                                    :disabled="isLoading || !tokenStatus.remembered"
                                    @click="fetchTags"
                                >
                                    <span v-if="!isLoading" class="fa-solid fa-arrows-rotate" aria-hidden="true"></span>
                                    <span v-else class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    <span>Refresh</span>
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
                                class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-2"
                                :disabled="filters.page === 1 || isLoading || !tokenStatus.remembered"
                                @click="changePage(-1)"
                            >
                                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                                <span>Previous</span>
                            </button>
                            <span class="text-secondary small">
                                Page {{ filters.page }} · showing {{ tags.length }} results
                            </span>
                            <button
                                type="button"
                                class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-2"
                                :disabled="(filters.perPage !== 'all' && typeof filters.perPage === 'number' && tags.length < filters.perPage) || isLoading || !tokenStatus.remembered"
                                @click="changePage(1)"
                            >
                                <span>Next</span>
                                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
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
                                    class="btn btn-outline-light d-inline-flex align-items-center gap-2"
                                    :disabled="isSubmitting || !tokenStatus.remembered"
                                    @click="resetForm"
                                >
                                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                    <span>Cancel</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="card bg-dark border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h5 text-white mb-3">Options</h2>
                        <div class="d-flex flex-column gap-3">
                            <div class="form-check">
                                <input
                                    id="delete-zero-posts"
                                    v-model="bulkDeleteOptions.deleteZeroPosts"
                                    type="checkbox"
                                    class="form-check-input"
                                    :disabled="!tokenStatus.remembered"
                                />
                                <label for="delete-zero-posts" class="form-check-label text-secondary small">
                                    Delete tags with 0 posts
                                </label>
                            </div>
                            <div class="form-check">
                                <input
                                    id="delete-weird-tags"
                                    v-model="bulkDeleteOptions.deleteWeirdTags"
                                    type="checkbox"
                                    class="form-check-input"
                                    :disabled="!tokenStatus.remembered"
                                />
                                <label for="delete-weird-tags" class="form-check-label text-secondary small">
                                    Delete tags with HTML entities (weird tags)
                                </label>
                                <div class="form-text text-secondary small mt-1">
                                    Tags containing HTML entities like &lt;a&gt; or encoded characters
                                </div>
                            </div>
                            <div v-if="bulkDeleteOptions.deleteZeroPosts && bulkDeleteOptions.deleteWeirdTags" class="alert alert-info border-info-subtle py-2 px-3 mb-0">
                                <div class="small">
                                    <strong>Both options selected:</strong> Tags matching <strong>either</strong> condition will be deleted (0 posts <strong>OR</strong> weird HTML).
                                </div>
                            </div>
                            <div v-if="tagsToDelete.length > 0" class="mt-3 pt-3 border-top border-secondary">
                                <div class="mb-2">
                                    <strong class="text-white small d-block mb-2">Tags to delete ({{ tagsToDelete.length }})</strong>
                                    <div class="small text-secondary">
                                        <div v-if="bulkDeleteOptions.deleteZeroPosts">
                                            <span class="text-warning">0 posts:</span> {{ zeroPostsCount }} tag(s)
                                        </div>
                                        <div v-if="bulkDeleteOptions.deleteWeirdTags">
                                            <span class="text-danger">HTML:</span> {{ weirdHtmlCount }} tag(s)
                                        </div>
                                        <div v-if="bulkDeleteOptions.deleteZeroPosts && bulkDeleteOptions.deleteWeirdTags && overlapCount > 0" class="text-info mt-1">
                                            <small>({{ overlapCount }} tag(s) match both conditions)</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="small mb-3" style="max-height: 200px; overflow-y: auto;">
                                    <div v-for="tag in tagsToDeleteWithReasons" :key="tag.id" class="mb-2 p-2 bg-dark border border-secondary rounded">
                                        <div class="d-flex align-items-start gap-2">
                                            <span class="text-danger">•</span>
                                            <div class="flex-grow-1">
                                                <div class="text-white fw-semibold mb-1">{{ tag.name }}</div>
                                                <div class="text-secondary d-flex flex-wrap gap-2">
                                                    <span>ID: {{ tag.id }}</span>
                                                    <span>•</span>
                                                    <span>Posts: {{ tag.count }}</span>
                                                </div>
                                                <div class="mt-1 d-flex flex-wrap gap-2">
                                                    <span v-if="tag.reasons.includes('zero-posts')" class="badge text-bg-warning text-dark">
                                                        <i class="fa-solid fa-0 me-1" aria-hidden="true"></i>
                                                        Zero posts
                                                    </span>
                                                    <span v-if="tag.reasons.includes('weird-html')" class="badge text-bg-danger">
                                                        <i class="fa-solid fa-code me-1" aria-hidden="true"></i>
                                                        Weird HTML
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-danger btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-2"
                                    :disabled="isBulkDeleting || tagsToDelete.length === 0 || !tokenStatus.remembered"
                                    @click="confirmBulkDelete"
                                >
                                    <span v-if="isBulkDeleting" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    <i v-else class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                    <span>Delete {{ tagsToDelete.length }} tag(s)</span>
                                </button>
                            </div>
                            <div v-else-if="bulkDeleteOptions.deleteZeroPosts || bulkDeleteOptions.deleteWeirdTags" class="text-secondary small">
                                No tags match the selected criteria.
                            </div>
                        </div>
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

        <!-- Bulk Delete Confirmation Modal -->
        <div
            ref="bulkDeleteModal"
            class="modal fade"
            tabindex="-1"
            aria-labelledby="bulkDeleteModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-scrollable">
                <div class="modal-content bg-dark border-secondary">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title text-white" id="bulkDeleteModalLabel">
                            <i class="fa-solid fa-triangle-exclamation text-danger me-2" aria-hidden="true"></i>
                            Confirm Bulk Delete
                        </h5>
                        <button
                            type="button"
                            class="btn-close btn-close-white"
                            aria-label="Close"
                            @click="closeBulkDeleteModal"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning border-warning-subtle mb-3">
                            <strong>Warning:</strong> This action bypasses the trash and is <strong>irreversible</strong>.
                        </div>
                        <p class="text-white mb-3">
                            You are about to delete <strong>{{ pendingBulkDelete.length }}</strong> tag(s):
                        </p>
                        <div class="small" style="max-height: 300px; overflow-y: auto;">
                            <div
                                v-for="tag in pendingBulkDelete"
                                :key="tag.id"
                                class="mb-2 p-2 bg-dark border border-secondary rounded"
                            >
                                <div class="d-flex align-items-start gap-2">
                                    <span class="text-danger">•</span>
                                    <div class="flex-grow-1">
                                        <div class="text-white fw-semibold mb-1">{{ tag.name }}</div>
                                        <div class="text-secondary">
                                            ID: {{ tag.id }} · Posts: {{ tag.count }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button
                            type="button"
                            class="btn btn-outline-light"
                            :disabled="isBulkDeleting"
                            @click="closeBulkDeleteModal"
                        >
                            <i class="fa-solid fa-xmark me-1" aria-hidden="true"></i>
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="btn btn-danger d-inline-flex align-items-center gap-2"
                            :disabled="isBulkDeleting"
                            @click="executeBulkDelete"
                        >
                            <span v-if="isBulkDeleting" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            <i v-else class="fa-solid fa-trash-can" aria-hidden="true"></i>
                            <span>Delete {{ pendingBulkDelete.length }} tag(s)</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Single Delete Confirmation Modal -->
        <div
            ref="deleteModal"
            class="modal fade"
            tabindex="-1"
            aria-labelledby="deleteModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog">
                <div class="modal-content bg-dark border-secondary">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title text-white" id="deleteModalLabel">
                            <i class="fa-solid fa-triangle-exclamation text-danger me-2" aria-hidden="true"></i>
                            Confirm Delete
                        </h5>
                        <button
                            type="button"
                            class="btn-close btn-close-white"
                            aria-label="Close"
                            @click="closeDeleteModal"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning border-warning-subtle mb-3">
                            <strong>Warning:</strong> This action bypasses the trash and is <strong>irreversible</strong>.
                        </div>
                        <p class="text-white mb-0">
                            Are you sure you want to delete tag <strong>"{{ pendingDeleteTag?.name }}"</strong>?
                        </p>
                        <div v-if="pendingDeleteTag" class="mt-3 small text-secondary">
                            ID: {{ pendingDeleteTag.id }} · Posts: {{ pendingDeleteTag.count }}
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button
                            type="button"
                            class="btn btn-outline-light"
                            :disabled="isSubmitting"
                            @click="closeDeleteModal"
                        >
                            <i class="fa-solid fa-xmark me-1" aria-hidden="true"></i>
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="btn btn-danger d-inline-flex align-items-center gap-2"
                            :disabled="isSubmitting"
                            @click="executeDelete"
                        >
                            <span v-if="isSubmitting" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            <i v-else class="fa-solid fa-trash-can" aria-hidden="true"></i>
                            <span>Delete</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from 'bootstrap/js/dist/modal';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { QuillEditor } from '@vueup/vue-quill';
import { handleApiError, isTransientError, type ApiErrorResponse } from '@/utils/errorHandler';
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
const isBulkDeleting = ref(false);
const editingTag = ref<Tag | null>(null);
const tokenStatus = ref<TokenStatus>({ remembered: false, username: null });
const alerts = ref<Array<{ id: string; variant: 'success' | 'danger'; message: string }>>([]);
const sortState = reactive<{ column: SortColumn; direction: SortDirection }>({ column: 'name', direction: 'asc' });
const bulkDeleteOptions = reactive({
    deleteZeroPosts: false,
    deleteWeirdTags: false,
});

// Modal refs and state
const bulkDeleteModal = ref<HTMLElement | null>(null);
const deleteModal = ref<HTMLElement | null>(null);
let bulkDeleteModalInstance: Modal | null = null;
let deleteModalInstance: Modal | null = null;
const pendingBulkDelete = ref<Tag[]>([]);
const pendingDeleteTag = ref<Tag | null>(null);

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
            const errorResponse = handleApiError(response.data, {
                showMessage: false, // We'll handle alert display manually
            });
            if (errorResponse) {
                pushAlert('danger', errorResponse.message || 'Unable to retrieve tags from WordPress.');
            }
            tags.value = [];
            return;
        }

        tags.value = response.data?.data?.items ?? [];
    } catch (error: unknown) {
        const errorResponse = handleApiError(error, {
            showMessage: false, // We'll handle alert display manually
        });
        if (errorResponse) {
            pushAlert('danger', errorResponse.message || 'Unable to retrieve tags from WordPress.');
        }
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
            const errorResponse = handleApiError(response.data, {
                showMessage: false, // We'll handle alert display manually
            });
            if (errorResponse) {
                pushAlert('danger', errorResponse.message || 'Unable to save tag in WordPress.');
            }
            return;
        }

        pushAlert('success', editingTag.value ? 'Tag updated.' : 'Tag created.');
        await fetchTags();
        resetForm();
    } catch (error: unknown) {
        const errorResponse = handleApiError(error, {
            showMessage: false, // We'll handle alert display manually
        });
        if (errorResponse) {
            pushAlert('danger', errorResponse.message || 'Unable to save tag in WordPress.');
        }
    } finally {
        isSubmitting.value = false;
    }
};

/**
 * Check if a tag name contains HTML entities or HTML tags (weird tags)
 * Examples: &lt;a&gt;, &quot;https://...&quot;, <a>...</a>
 */
const isWeirdTag = (tag: Tag): boolean => {
    const name = tag.name;
    // Check for HTML entities (encoded characters)
    if (/&[a-z]+;/i.test(name) || /&#\d+;/.test(name) || /&#x[\da-f]+;/i.test(name)) {
        return true;
    }
    // Check for HTML tags (like <a>, </a>, <div>, etc.)
    if (/<[^>]+>/.test(name)) {
        return true;
    }
    return false;
};

/**
 * Get list of tags that match bulk delete criteria
 */
const tagsToDelete = computed<Tag[]>(() => {
    if (!bulkDeleteOptions.deleteZeroPosts && !bulkDeleteOptions.deleteWeirdTags) {
        return [];
    }

    const tagsToDeleteList: Tag[] = [];
    const seenIds = new Set<number>();

    tags.value.forEach((tag) => {
        let shouldDelete = false;

        // Check if tag matches deleteZeroPosts criteria
        if (bulkDeleteOptions.deleteZeroPosts && tag.count === 0) {
            shouldDelete = true;
        }

        // Check if tag matches deleteWeirdTags criteria
        if (bulkDeleteOptions.deleteWeirdTags && isWeirdTag(tag)) {
            shouldDelete = true;
        }

        // Only add if matches at least one criteria and not already added
        if (shouldDelete && !seenIds.has(tag.id)) {
            tagsToDeleteList.push(tag);
            seenIds.add(tag.id);
        }
    });

    // Sort by ID for consistent display
    return tagsToDeleteList.sort((a, b) => a.id - b.id);
});

interface TagWithReasons extends Tag {
    reasons: Array<'zero-posts' | 'weird-html'>;
}

const tagsToDeleteWithReasons = computed<TagWithReasons[]>(() => {
    return tagsToDelete.value.map((tag) => {
        const reasons: Array<'zero-posts' | 'weird-html'> = [];
        
        if (bulkDeleteOptions.deleteZeroPosts && tag.count === 0) {
            reasons.push('zero-posts');
        }
        
        if (bulkDeleteOptions.deleteWeirdTags && isWeirdTag(tag)) {
            reasons.push('weird-html');
        }
        
        return {
            ...tag,
            reasons,
        };
    });
});

// Count breakdown for display
const zeroPostsCount = computed<number>(() => {
    if (!bulkDeleteOptions.deleteZeroPosts) {
        return 0;
    }
    return tags.value.filter((tag) => tag.count === 0).length;
});

const weirdHtmlCount = computed<number>(() => {
    if (!bulkDeleteOptions.deleteWeirdTags) {
        return 0;
    }
    return tags.value.filter((tag) => isWeirdTag(tag)).length;
});

const overlapCount = computed<number>(() => {
    if (!bulkDeleteOptions.deleteZeroPosts || !bulkDeleteOptions.deleteWeirdTags) {
        return 0;
    }
    return tags.value.filter((tag) => tag.count === 0 && isWeirdTag(tag)).length;
});

const confirmDelete = (tag: Tag): void => {
    if (!tokenStatus.value.remembered) {
        return; // Alert already shown at top of page
    }

    pendingDeleteTag.value = tag;
    deleteModalInstance?.show();
};

const closeDeleteModal = (): void => {
    deleteModalInstance?.hide();
    pendingDeleteTag.value = null;
};

const executeDelete = async (): Promise<void> => {
    const tag = pendingDeleteTag.value;
    if (!tag) {
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
            const errorResponse = handleApiError(response.data, {
                showMessage: false, // We'll handle alert display manually
            });
            if (errorResponse) {
                pushAlert('danger', errorResponse.message || 'Unable to delete tag from WordPress.');
            }
            return;
        }

        pushAlert('success', 'Tag deleted.');
        closeDeleteModal();
        if (editingTag.value?.id === tag.id) {
            resetForm();
        }
        await fetchTags();
    } catch (error: unknown) {
        const errorResponse = handleApiError(error, {
            showMessage: false, // We'll handle alert display manually
        });
        if (errorResponse) {
            pushAlert('danger', errorResponse.message || 'Unable to delete tag from WordPress.');
        }
    } finally {
        isSubmitting.value = false;
    }
};

const confirmBulkDelete = (): void => {
    if (!tokenStatus.value.remembered) {
        return; // Alert already shown at top of page
    }

    const tagsToDeleteList = tagsToDelete.value;
    if (tagsToDeleteList.length === 0) {
        return;
    }

    pendingBulkDelete.value = tagsToDeleteList;
    bulkDeleteModalInstance?.show();
};

const closeBulkDeleteModal = (): void => {
    bulkDeleteModalInstance?.hide();
    pendingBulkDelete.value = [];
};

const executeBulkDelete = async (): Promise<void> => {
    await deleteBulkTags(pendingBulkDelete.value);
    closeBulkDeleteModal();
};

const deleteBulkTags = async (tagsToDeleteList: Tag[]): Promise<void> => {
    if (tagsToDeleteList.length === 0) {
        return;
    }

    isBulkDeleting.value = true;

    try {
        // Queue bulk delete via backend (handles deletion in background queue)
        const tagIds = tagsToDeleteList.map((tag) => tag.id);
        const response = await window.axios.delete('/api/v1/wordpress/tags/bulk', {
            data: {
                tag_ids: tagIds,
                force: true,
            },
        });

        // Handle structured error responses from API
        if (response.data?.ok === false) {
            const errorResponse = handleApiError(response.data, {
                showMessage: false, // We'll handle alert display manually
            });
            if (errorResponse) {
                pushAlert('danger', errorResponse.message || 'Unable to queue tags for deletion.');
            }
            return;
        }

        pushAlert('success', `Queued ${tagIds.length} tag(s) for deletion. Tags will be deleted in the background.`);

        // Reset form if editing a deleted tag
        if (editingTag.value && tagsToDeleteList.find((t) => t.id === editingTag.value?.id)) {
            resetForm();
        }

        // Refresh tags list after a short delay to allow queue processing to start
        setTimeout(async () => {
            await fetchTags();
        }, 2000);

        // Reset bulk delete options
        bulkDeleteOptions.deleteZeroPosts = false;
        bulkDeleteOptions.deleteWeirdTags = false;
    } catch (error: unknown) {
        const errorResponse = handleApiError(error, {
            showMessage: false, // We'll handle alert display manually
        });
        if (errorResponse) {
            pushAlert('danger', errorResponse.message || 'Unable to queue tags for deletion.');
        }
    } finally {
        isBulkDeleting.value = false;
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
        const errorResponse = handleApiError(error, {
            showMessage: false, // We'll handle alert display manually
        });
        if (errorResponse) {
            pushAlert('danger', errorResponse.message || 'Unable to refresh token status.');
        }
    }
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

    // Initialize Bootstrap modals
    if (bulkDeleteModal.value) {
        bulkDeleteModalInstance = new Modal(bulkDeleteModal.value);
    }
    if (deleteModal.value) {
        deleteModalInstance = new Modal(deleteModal.value);
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
