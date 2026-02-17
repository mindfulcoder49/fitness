<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    section: Object,
});
</script>

<template>
    <Link
        :href="route('magazine.section', section.slug)"
        class="inline-block text-xs font-medium px-2 py-0.5 rounded bg-theme-accent/10 text-theme-accent hover:bg-theme-accent/20 transition"
    >
        {{ section.name }}
    </Link>
</template>
