<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MagazineLayout from '@/Layouts/MagazineLayout.vue';
import ArticleGrid from '@/Components/Magazine/ArticleGrid.vue';

defineOptions({ layout: MagazineLayout });

defineProps({
    contributor: Object,
    articles: Object,
});
</script>

<template>
    <Head :title="`${contributor.name} - AI Survival Magazine`" />

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <!-- Contributor Header -->
        <div class="mb-10 flex items-start gap-6">
            <img
                v-if="contributor.photo_url"
                :src="contributor.photo_url"
                :alt="contributor.name"
                class="w-24 h-24 rounded-full object-cover"
            />
            <div v-else class="w-24 h-24 rounded-full bg-theme-elevated flex items-center justify-center text-2xl font-bold text-theme-text-muted">
                {{ contributor.name.charAt(0) }}
            </div>
            <div>
                <h1 class="text-3xl font-bold text-theme-text-primary">{{ contributor.name }}</h1>
                <p v-if="contributor.bio" class="mt-2 text-theme-text-secondary max-w-2xl">{{ contributor.bio }}</p>
            </div>
        </div>

        <!-- Articles -->
        <h2 class="text-xl font-semibold text-theme-text-primary mb-6">Articles</h2>

        <ArticleGrid v-if="articles.data?.length" :articles="articles.data" />

        <div v-else class="text-center py-16">
            <p class="text-theme-text-muted">No published articles yet.</p>
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
