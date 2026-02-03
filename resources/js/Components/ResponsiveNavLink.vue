<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-theme-accent text-start text-base font-medium text-theme-accent-text bg-theme-accent/10 focus:outline-none focus:text-theme-accent-text focus:bg-theme-accent/20 focus:border-theme-accent transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-theme-text-muted hover:text-theme-text-primary hover:bg-theme-elevated hover:border-theme-border focus:outline-none focus:text-theme-text-primary focus:bg-theme-elevated focus:border-theme-border transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
