<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    tag: Object,
});
</script>

<template>
    <Link
        :href="route('magazine.tag', tag.slug)"
        class="inline-block text-xs font-medium px-2.5 py-1 rounded-full border border-theme-border text-theme-text-muted hover:text-theme-text-primary hover:border-theme-accent transition"
    >
        {{ tag.name }}
    </Link>
</template>
