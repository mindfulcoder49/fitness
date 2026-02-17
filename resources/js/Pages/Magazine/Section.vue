<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MagazineLayout from '@/Layouts/MagazineLayout.vue';
import ArticleGrid from '@/Components/Magazine/ArticleGrid.vue';

defineOptions({ layout: MagazineLayout });

defineProps({
    section: Object,
    articles: Object,
});
</script>

<template>
    <Head :title="`${section.name} - AI Survival Magazine`" />

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <!-- Section Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-theme-text-primary">{{ section.name }}</h1>
            <p v-if="section.description" class="mt-2 text-theme-text-secondary">{{ section.description }}</p>
        </div>

        <!-- Vertical Filter Pills -->
        <div v-if="section.verticals?.length" class="mb-8 flex flex-wrap gap-2">
            <Link
                :href="route('magazine.section', section.slug)"
                class="px-3 py-1.5 text-sm font-medium rounded-full border border-theme-accent bg-theme-accent/10 text-theme-accent"
            >
                All
            </Link>
            <Link
                v-for="vertical in section.verticals"
                :key="vertical.id"
                :href="route('magazine.vertical', [section.slug, vertical.slug])"
                class="px-3 py-1.5 text-sm font-medium rounded-full border border-theme-border text-theme-text-muted hover:text-theme-text-primary hover:border-theme-accent transition"
            >
                {{ vertical.name }}
            </Link>
        </div>

        <!-- Articles -->
        <ArticleGrid v-if="articles.data?.length" :articles="articles.data" />

        <div v-else class="text-center py-16">
            <p class="text-theme-text-muted">No articles in this section yet.</p>
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
