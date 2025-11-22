<template>
    <div class="empty-state text-center py-5">
        <i v-if="icon" :class="icon" class="empty-state-icon text-muted mb-3" aria-hidden="true"></i>
        <h5 v-if="title" class="text-muted mb-2">{{ title }}</h5>
        <p v-if="message" class="text-muted mb-3">{{ message }}</p>
        <div v-if="showRetry && onRetry" class="d-flex justify-content-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="onRetry">
                <i class="fa-solid fa-arrows-rotate me-2" aria-hidden="true"></i>
                <span>Retry</span>
            </button>
        </div>
        <slot></slot>
    </div>
</template>

<script setup lang="ts">
interface Props {
    title?: string;
    message?: string;
    icon?: string;
    showRetry?: boolean;
    onRetry?: () => void;
}

withDefaults(defineProps<Props>(), {
    showRetry: false,
});
</script>

<style scoped>
.empty-state {
    min-height: 200px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.empty-state-icon {
    font-size: 3rem;
    opacity: 0.5;
}
</style>

