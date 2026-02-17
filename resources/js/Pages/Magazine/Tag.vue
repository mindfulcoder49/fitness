<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MagazineLayout from '@/Layouts/MagazineLayout.vue';
import ArticleGrid from '@/Components/Magazine/ArticleGrid.vue';

defineOptions({ layout: MagazineLayout });

defineProps({
    tag: Object,
    articles: Object,
});
</script>

<template>
    <Head :title="`Tag: ${tag.name} - AI Survival Magazine`" />

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <p class="text-sm text-theme-text-muted mb-1">Tagged</p>
            <h1 class="text-3xl font-bold text-theme-text-primary">{{ tag.name }}</h1>
        </div>

        <ArticleGrid v-if="articles.data?.length" :articles="articles.data" />

        <div v-else class="text-center py-16">
            <p class="text-theme-text-muted">No articles with this tag yet.</p>
        </div>

        <!-- Pagination -->
        <div v-if="articles.links?.length > 3" class="mt-10 flex justify-center gap-2">
            <template v-for="link in articles.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="px-3 py-1.5 text-sm rounded border transition"
                    :class="link.active ? 'bg-theme-accent text-white border-theme-accent' : 'border-theme-border text-theme-text-muted hover:text-theme-text-primary'"
                    v-html="link.label"
                />
                <span v-else class="px-3 py-1.5 text-sm text-theme-text-faint" v-html="link.label" />
            </template>
        </div>
    </div>
</template>
