<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    contributor: Object,
});
</script>

<template>
    <div class="flex items-center gap-2">
        <Link
            :href="route('magazine.contributor', contributor.slug)"
            class="text-sm font-medium text-theme-link hover:text-theme-link-hover transition"
        >
            {{ contributor.name }}
        </Link>
        <span v-if="contributor.pivot?.role" class="text-xs text-theme-text-faint">
            ({{ contributor.pivot.role }})
        </span>
    </div>
</template>
