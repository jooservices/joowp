<template>
    <div v-if="show" class="alert alert-danger border border-danger-subtle d-flex align-items-center gap-3" role="alert">
        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
        <div class="flex-grow-1">
            <strong v-if="title" class="me-2">{{ title }}</strong>
            <span>{{ message }}</span>
        </div>
        <button
            v-if="dismissible"
            type="button"
            class="btn-close btn-close-white"
            aria-label="Close"
            @click="$emit('dismiss')"
        ></button>
    </div>
</template>

<script setup lang="ts">
interface Props {
    message: string;
    title?: string;
    show?: boolean;
    dismissible?: boolean;
}

withDefaults(defineProps<Props>(), {
    show: true,
    dismissible: false,
});

defineEmits<{
    dismiss: [];
}>();
</script>

