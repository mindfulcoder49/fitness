<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ArticleGrid from '@/Components/Magazine/ArticleGrid.vue';

defineOptions({ layout: AppLayout });

defineProps({
    section: Object,
    vertical: Object,
    articles: Object,
});
</script>

<template>
    <Head :title="`${vertical.name} - ${section.name} - ${$page.props.appName}`" />

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumb -->
        <nav class="mb-6 text-sm text-theme-text-muted">
            <Link :href="route('magazine.home')" class="hover:text-theme-text-primary transition">Home</Link>
            <span class="mx-2">/</span>
            <Link :href="route('magazine.section', section.slug)" class="hover:text-theme-text-primary transition">{{ section.name }}</Link>
            <span class="mx-2">/</span>
            <span class="text-theme-text-primary">{{ vertical.name }}</span>
        </nav>

        <!-- Vertical Header -->
        <div class="mb-8 flex items-start gap-6">
            <img v-if="vertical.image_url" :src="vertical.image_url" :alt="vertical.name" class="w-20 h-20 rounded-lg object-cover" />
            <div>
                <h1 class="text-3xl font-bold text-theme-text-primary">{{ vertical.name }}</h1>
                <p v-if="vertical.description" class="mt-2 text-theme-text-secondary">{{ vertical.description }}</p>
            </div>
        </div>

        <!-- Articles -->
        <ArticleGrid v-if="articles.data?.length" :articles="articles.data" />

        <div v-else class="text-center py-16">
            <p class="text-theme-text-muted">No articles in this series yet.</p>
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
