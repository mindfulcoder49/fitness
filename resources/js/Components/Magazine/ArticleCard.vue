<script setup>
import { Link } from '@inertiajs/vue3';
import SectionBadge from './SectionBadge.vue';

defineProps({
    article: Object,
});

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric'
    });
}
</script>

<template>
    <Link :href="route('magazine.article', article.slug)" class="group block">
        <div class="bg-theme-card border border-theme-border rounded-lg overflow-hidden hover:border-theme-accent transition">
            <div v-if="article.featured_image_url" class="aspect-[16/9] overflow-hidden">
                <img
                    :src="article.featured_image_url"
                    :alt="article.title"
                    class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                />
            </div>
            <div v-else class="aspect-[16/9] bg-theme-elevated flex items-center justify-center">
                <svg class="w-12 h-12 text-theme-text-faint" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                </svg>
            </div>
            <div class="p-4">
                <div class="flex items-center gap-2 mb-2">
                    <SectionBadge v-if="article.section" :section="article.section" />
                    <span v-if="article.access === 'members'" class="text-xs px-1.5 py-0.5 bg-theme-warning/20 text-theme-warning rounded">Members</span>
                </div>
                <h3 class="text-lg font-semibold text-theme-text-primary group-hover:text-theme-accent transition line-clamp-2">
                    {{ article.title }}
                </h3>
                <p v-if="article.excerpt" class="mt-1 text-sm text-theme-text-muted line-clamp-2">
                    {{ article.excerpt }}
                </p>
                <div class="mt-3 flex items-center justify-between text-xs text-theme-text-faint">
                    <span v-if="article.contributors?.length">
                        {{ article.contributors.map(c => c.name).join(', ') }}
                    </span>
                    <span>{{ formatDate(article.published_at) }}</span>
                </div>
            </div>
        </div>
    </Link>
</template>
