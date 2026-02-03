<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center rounded-md border border-theme-border bg-theme-btn-secondary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-theme-btn-secondary-text shadow-sm transition duration-150 ease-in-out hover:bg-theme-btn-secondary-hover focus:outline-none focus:ring-2 focus:ring-theme-accent-ring focus:ring-offset-2 disabled:opacity-25"
    >
        <slot />
    </button>
</template>
