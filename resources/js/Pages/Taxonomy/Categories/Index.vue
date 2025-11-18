<template>
    <div class="categories-page container-fluid py-5 text-white">
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
                                    :disabled="!tokenStatus.remembered"
                                    @input="debouncedSearch"
                                />
                            </div>
                            <div class="d-flex gap-2 align-items-center ms-md-auto">
                                <label class="text-secondary small mb-0">Per page</label>
                                <select
                                    v-model.number="filters.perPage"
                                    class="form-select form-select-sm bg-transparent text-white border-secondary-subtle"
                                    :disabled="!tokenStatus.remembered"
                                >
                                    <option v-for="option in perPageOptions" :key="option" :value="option">
                                        {{ option }}
                                    </option>
                                </select>
                                <button
                                    type="button"
                                    class="btn btn-tertiary btn-sm"
                                    :disabled="isLoading || !tokenStatus.remembered"
                                    @click="fetchCategories"
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
                                        <th scope="col" class="sortable" @click="cycleSort('parent')">
                                            <span class="sortable-inner">
                                                <span>Parent</span>
                                                <i :class="sortIcon('parent')" aria-hidden="true"></i>
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
                                        <td colspan="6" class="text-center">
                                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                            Loading categories…
                                        </td>
                                    </tr>
                                    <tr v-else-if="categories.length === 0">
                                        <td colspan="6" class="text-center text-secondary">
                                            No categories match the current filters.
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="category in displayCategories"
                                        :key="category.id"
                                        class="table-row"
                                        @click="selectForEdit(category)"
                                    >
                                        <td>
                                            <span class="badge bg-secondary-subtle text-uppercase text-dark fw-semibold">#{{ category.id }}</span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold hierarchy-label">
                                                {{ hierarchyLabel(category) }}
                                            </div>
                                            <div class="text-secondary small" v-html="sanitizedDescription(category.description)"></div>
                                        </td>
                                        <td>{{ category.slug }}</td>
                                        <td>
                                            <span v-if="category.parent === 0" class="badge text-bg-secondary rounded-pill">Root</span>
                                            <span v-else class="badge text-bg-dark border border-secondary rounded-pill">
                                                {{ resolveParentName(category.parent) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ category.count }}</span>
                                        </td>
                                        <td class="text-end">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-danger d-inline-flex align-items-center justify-content-center gap-2 px-3"
                                                :disabled="isSubmitting || !tokenStatus.remembered"
                                                @click.stop="confirmDelete(category)"
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
                                Page {{ filters.page }} · showing {{ categories.length }} results
                            </span>
                            <button
                                type="button"
                                class="btn btn-outline-light btn-sm"
                                :disabled="categories.length < filters.perPage || isLoading || !tokenStatus.remembered"
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
                                    :disabled="!tokenStatus.remembered"
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
                                    :disabled="!tokenStatus.remembered"
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
                                    :disabled="!tokenStatus.remembered"
                                ></textarea>
                            </div>
                            <div>
                                <label for="category-parent" class="form-label text-secondary small">Parent</label>
                                <div class="form-check mb-2">
                                    <input
                                        id="include-trashed"
                                        v-model="includeTrashed"
                                        type="checkbox"
                                        class="form-check-input"
                                        :disabled="!tokenStatus.remembered"
                                        @change="fetchParentOptions"
                                    />
                                    <label for="include-trashed" class="form-check-label text-secondary small">
                                        Include trashed categories
                                    </label>
                                </div>
                                <select
                                    id="category-parent"
                                    v-model.number="form.parent"
                                    class="form-select bg-transparent border-secondary-subtle text-white"
                                    :disabled="!tokenStatus.remembered || isLoadingParents"
                                >
                                    <option v-if="parentOptions.length === 1 && parentOptions[0]?.value === 0 && editingCategory" value="0" disabled>
                                        No valid parent categories available
                                    </option>
                                    <option v-for="option in parentOptions" :key="option.value" :value="option.value"
                                        :disabled="editingCategory?.id === option.value"
                                        :style="{ paddingLeft: `${option.depth * 0.85}rem`, color: option.isTrashed === true ? '#6c757d' : 'inherit' }"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                                <div v-if="parentOptions.length === 1 && parentOptions[0]?.value === 0 && editingCategory" class="form-text text-secondary small mt-1">
                                    No valid parent categories available
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-fill d-flex align-items-center justify-content-center gap-2" :disabled="isSubmitting || !tokenStatus.remembered">
                                    <span v-if="isSubmitting" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    <span v-else class="fa-solid" :class="editingCategory ? 'fa-floppy-disk' : 'fa-plus-circle'"></span>
                                    <span>{{ editingCategory ? 'Update category' : 'Create category' }}</span>
                                </button>
                                <button
                                    v-if="editingCategory"
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

defineOptions({ layout: AppLayout });

interface Category {
    id: number;
    name: string;
    slug: string;
    description: string;
    parent: number;
    count: number;
}

interface ParentNode {
    name: string;
    parent: number;
}

interface ParentOption {
    value: number;
    label: string;
    depth: number;
    isTrashed?: boolean;
}

interface CategoryRow extends Category {
    depth: number;
}

interface TokenStatus {
    remembered: boolean;
    username?: string | null;
}

type SortColumn = 'hierarchy' | 'id' | 'name' | 'slug' | 'parent' | 'posts';
type SortDirection = 'asc' | 'desc';

const categories = ref<Category[]>([]);
const isLoading = ref(false);
const isSubmitting = ref(false);
const isLoadingParents = ref(false);
const editingCategory = ref<Category | null>(null);
const tokenStatus = ref<TokenStatus>({ remembered: false, username: null });
const includeTrashed = ref(false);
const parentOptions = ref<ParentOption[]>([{ value: 0, label: 'None', depth: 0 }]);
const alerts = ref<Array<{ id: string; variant: 'success' | 'danger'; message: string }>>([]);
const perPageOptions = [10, 20, 40, 80];
const sortState = reactive<{ column: SortColumn; direction: SortDirection }>({ column: 'hierarchy', direction: 'asc' });
const parentRegistry = reactive(new Map<number, ParentNode>());
const parentNameMap = computed(() => {
    const map = new Map<number, string>([[0, 'Root']]);

    parentRegistry.forEach((node, id) => {
        map.set(id, node.name);
    });

    categories.value.forEach((category) => {
        map.set(category.id, category.name);
    });

    return map;
});
const treeContext = computed(() => {
    const items = categories.value;
    const depthMap = new Map<number, number>();
    const rows: CategoryRow[] = [];

    if (items.length === 0) {
        return { rows, depthMap };
    }

    const knownIds = new Set(items.map((item) => item.id));
    const children = new Map<number, Category[]>();

    items.forEach((item) => {
        const parentId = knownIds.has(item.parent) ? item.parent : 0;
        const bucket = children.get(parentId) ?? [];
        bucket.push(item);
        children.set(parentId, bucket);
    });

    const naturalSort = (list: Category[]): Category[] =>
        list.sort((first, second) => first.name.localeCompare(second.name));

    const traversed = new Set<number>();

    const traverse = (parentId: number, depth: number): void => {
        const siblings = children.get(parentId);
        if (! siblings?.length) {
            return;
        }

        naturalSort(siblings);

        siblings.forEach((child) => {
            traversed.add(child.id);
            depthMap.set(child.id, depth);
            rows.push({ ...child, depth });
            traverse(child.id, depth + 1);
        });
    };

    traverse(0, 0);

    items.forEach((item) => {
        if (! traversed.has(item.id)) {
            depthMap.set(item.id, 0);
            rows.push({ ...item, depth: 0 });
        }
    });

    return { rows, depthMap };
});

const displayCategories = computed<CategoryRow[]>(() => {
    const { rows, depthMap } = treeContext.value;

    if (sortState.column === 'hierarchy') {
        return rows;
    }

    return categories.value
        .map((category) => ({
            ...category,
            depth: depthMap.get(category.id) ?? 0,
        }))
        .sort(compareCategories);
});
const fetchParentOptions = async (): Promise<void> => {
    if (!tokenStatus.value.remembered) {
        parentOptions.value = [{ value: 0, label: 'None', depth: 0 }];
        return;
    }

    isLoadingParents.value = true;
    try {
        const params: Record<string, unknown> = {
            include_trashed: includeTrashed.value,
        };

        if (editingCategory.value?.id) {
            params.exclude = editingCategory.value.id;
        }

        const response = await window.axios.get('/api/v1/wordpress/categories/parents', { params });

        const items = response.data?.data?.items ?? [];
        const options: ParentOption[] = [{ value: 0, label: 'None', depth: 0 }];

        items.forEach((item: { id: number; name: string; depth: number; status?: string }) => {
            const prefix = item.depth > 0 ? `${'— '.repeat(item.depth)}` : '';
            options.push({
                value: item.id,
                label: `${prefix}${item.name}`,
                depth: item.depth,
                isTrashed: item.status === 'trash',
            });
        });

        parentOptions.value = options;
    } catch (error: unknown) {
        pushAlert('danger', extractErrorMessage(error));
        parentOptions.value = [{ value: 0, label: 'None', depth: 0 }];
    } finally {
        isLoadingParents.value = false;
    }
};

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
    if (!tokenStatus.value.remembered) {
        categories.value = [];
        parentRegistry.clear();
        return;
    }

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
        syncParentRegistry(categories.value);
    } catch (error: unknown) {
        pushAlert('danger', extractErrorMessage(error));
        categories.value = [];
        parentRegistry.clear();
    } finally {
        isLoading.value = false;
    }
};

const submitCategory = async (): Promise<void> => {
    if (!tokenStatus.value.remembered) {
        return; // Alert already shown at top of page
    }

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

        await Promise.all([fetchCategories(), fetchParentOptions()]);
        resetForm();
    } catch (error: unknown) {
        pushAlert('danger', extractErrorMessage(error));
    } finally {
        isSubmitting.value = false;
    }
};

const confirmDelete = async (category: Category): Promise<void> => {
    if (!tokenStatus.value.remembered) {
        return; // Alert already shown at top of page
    }

    if (! window.confirm(`Delete "${category.name}"? This bypasses the trash and is irreversible.`)) {
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
    ensureParentRegistered(category.id, category.parent, category.name);
    ensureParentRegistered(category.parent);
    void fetchParentOptions();
};

const resetForm = (): void => {
    editingCategory.value = null;
    form.name = '';
    form.slug = '';
    form.description = '';
    form.parent = 0;
    void fetchParentOptions();
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

const hierarchyLabel = (category: CategoryRow): string => {
    const prefix = category.depth > 0 ? `${'— '.repeat(category.depth)} ` : '';

    return `${prefix}${category.name}`;
};

const resolveParentName = (parentId: number): string => {
    return parentNameMap.value.get(parentId) ?? `Category #${parentId}`;
};

const compareCategories = (first: CategoryRow, second: CategoryRow): number => {
    const directionMultiplier = sortState.direction === 'asc' ? 1 : -1;

    switch (sortState.column) {
        case 'id':
            return (first.id - second.id) * directionMultiplier;
        case 'slug':
            return first.slug.localeCompare(second.slug) * directionMultiplier;
        case 'parent':
            return resolveParentName(first.parent).localeCompare(resolveParentName(second.parent)) * directionMultiplier;
        case 'posts':
            return (first.count - second.count) * directionMultiplier;
        case 'name':
            return first.name.localeCompare(second.name) * directionMultiplier;
        case 'hierarchy':
        default:
            return 0;
    }
};

const cycleSort = (column: SortColumn): void => {
    if (sortState.column === column) {
        if (sortState.direction === 'asc') {
            sortState.direction = 'desc';
        } else {
            sortState.column = 'hierarchy';
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

const syncParentRegistry = (items: Category[]): void => {
    items.forEach((item) => {
        ensureParentRegistered(item.id, item.parent, item.name);
        ensureParentRegistered(item.parent);
    });
};

const ensureParentRegistered = (id: number, parentId = 0, name?: string): void => {
    if (id <= 0) {
        return;
    }

    const normalizedParent = parentId > 0 ? parentId : 0;
    const existing = parentRegistry.get(id);

    if (existing) {
        const updatedNode: ParentNode = {
            name: name ?? existing.name,
            parent: normalizedParent !== 0 ? normalizedParent : existing.parent,
        };

        parentRegistry.set(id, updatedNode);
        return;
    }

    parentRegistry.set(id, {
        name: name ?? `Category #${id}`,
        parent: normalizedParent,
    });
};

watch(
    () => filters.perPage,
    () => {
        filters.page = 1;
        void fetchCategories();
    }
);

onMounted(async () => {
    await refreshTokenStatus();
    if (tokenStatus.value.remembered) {
        await Promise.all([fetchCategories(), fetchParentOptions()]);
    }
});

watch(
    () => tokenStatus.value.remembered,
    (remembered) => {
        if (remembered) {
            void Promise.all([fetchCategories(), fetchParentOptions()]);
        } else {
            categories.value = [];
            parentRegistry.clear();
            parentOptions.value = [{ value: 0, label: 'None', depth: 0 }];
        }
    }
);
</script>

<style scoped>
.categories-page {
    min-height: calc(100vh - 6rem);
    background: linear-gradient(180deg, #1a2234 0%, #101726 55%, #0d1421 100%);
}

.categories-page .card {
    background: #1f2a3c !important;
    border: 1px solid rgba(58, 72, 99, 0.6) !important;
    box-shadow: 0 18px 35px rgba(10, 13, 22, 0.45);
}

.categories-page header p,
.categories-page header h1,
.categories-page header strong {
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

.categories-page .form-control::placeholder,
.categories-page .form-select option {
    color: rgba(159, 174, 203, 0.8);
}

.table-row:hover {
    cursor: pointer;
    background: rgba(59, 130, 246, 0.18);
}

.hierarchy-label {
    white-space: pre-wrap;
    letter-spacing: 0.01em;
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
    background: rgba(56, 180, 110, 0.2);
    color: #63f0af;
}

.chip-empty {
    background: rgba(82, 103, 138, 0.2);
    color: rgba(200, 211, 233, 0.85);
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

.categories-page .btn-outline-info {
    color: #36b9cc;
    border-color: rgba(54, 185, 204, 0.45);
}

.categories-page .btn-outline-info:hover,
.categories-page .btn-outline-info:focus {
    color: #0f172a;
    background: linear-gradient(135deg, #36b9cc, #5bc2d6);
    border-color: #36b9cc;
}

.categories-page .btn-outline-danger {
    color: #f66d6d;
    border-color: rgba(246, 109, 109, 0.45);
}

.categories-page .btn-outline-danger:hover,
.categories-page .btn-outline-danger:focus {
    color: #0f172a;
    background: linear-gradient(135deg, #f87171, #f97384);
    border-color: #f87171;
}

.categories-page .btn-outline-light {
    color: #8faadc;
    border-color: rgba(143, 170, 220, 0.45);
}

.categories-page .btn-outline-light:hover,
.categories-page .btn-outline-light:focus {
    color: #0f172a;
    background: linear-gradient(135deg, #8faadc, #a5b8e6);
    border-color: #8faadc;
}

.categories-page .btn-primary {
    background: linear-gradient(135deg, #4e73df, #2e59d9);
    border: none;
    box-shadow: 0 10px 25px rgba(46, 89, 217, 0.35);
}

.categories-page .btn-primary:disabled,
.categories-page .btn-primary:focus,
.categories-page .btn-primary:hover {
    background: linear-gradient(135deg, #5a82ef, #3b6bea);
    border: none;
}

.categories-page .badge.text-bg-dark {
    background: rgba(55, 65, 81, 0.85) !important;
    border-color: rgba(148, 163, 184, 0.28) !important;
    color: rgba(209, 213, 219, 0.95) !important;
}

.categories-page .table-dark {
    --bs-table-bg: rgba(16, 23, 35, 0.92);
    --bs-table-striped-bg: rgba(28, 39, 61, 0.92);
    --bs-table-hover-bg: rgba(46, 89, 217, 0.18);
    --bs-table-color: rgba(238, 242, 255, 0.95);
}

.categories-page .table-dark thead tr {
    background: linear-gradient(135deg, rgba(46, 89, 217, 0.28), rgba(59, 130, 246, 0.18));
}

.categories-page .table-dark tbody tr {
    border-color: rgba(54, 65, 86, 0.55);
}

.categories-page .table-dark tbody tr td:first-child .fw-semibold {
    color: rgba(247, 249, 255, 0.98);
}

.categories-page .table-dark tbody tr td:first-child .text-secondary {
    color: rgba(177, 192, 219, 0.92) !important;
}

.categories-page .alert-warning {
    background: rgba(255, 193, 7, 0.12);
    border-color: rgba(255, 193, 7, 0.45) !important;
    color: rgba(255, 214, 102, 0.95);
}

.categories-page .alert button {
    color: rgba(16, 24, 39, 0.9);
    background: rgba(255, 214, 102, 0.9);
    border: none;
}

.categories-page .input-group-text {
    background: rgba(34, 48, 71, 0.9);
    border-color: rgba(68, 85, 120, 0.55);
    color: rgba(188, 202, 230, 0.92);
}
</style>
