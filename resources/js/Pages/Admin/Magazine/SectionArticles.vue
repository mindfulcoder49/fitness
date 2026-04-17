<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    section: Object,
    articles: Array,
});

const orderedArticles = ref([...props.articles]);

watch(() => props.articles, (articles) => {
    orderedArticles.value = [...articles];
});

function reorder(index, direction) {
    const nextIndex = index + direction;
    if (nextIndex < 0 || nextIndex >= orderedArticles.value.length) return;

    [orderedArticles.value[index], orderedArticles.value[nextIndex]] = [orderedArticles.value[nextIndex], orderedArticles.value[index]];

    router.post(route('admin.magazine.sections.articles.reorder', props.section.id), {
        order: orderedArticles.value.map(article => article.id),
    }, {
        preserveScroll: true,
        preserveState: true,
    });
}

function formatDate(dateStr) {
    if (!dateStr) return 'Unscheduled';

    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

const statusColors = {
    draft: 'bg-theme-text-muted/20 text-theme-text-muted',
    scheduled: 'bg-theme-warning/20 text-theme-warning',
    published: 'bg-theme-success/20 text-theme-success',
    archived: 'bg-theme-danger/20 text-theme-danger',
};
</script>

<template>
    <Head :title="`Arrange ${section.name} Articles`" />

    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-theme-text-primary">Arrange {{ section.name }} Articles</h1>
                <p class="text-sm text-theme-text-muted mt-1">
                    The homepage shows the first {{ section.homepage_article_limit }} article<span v-if="section.homepage_article_limit !== 1">s</span> from this order.
                    Draft and archived items keep their place but only published items render publicly.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.magazine.sections.index')" class="px-4 py-2 bg-theme-btn-secondary text-theme-btn-secondary-text rounded-md text-sm font-medium hover:bg-theme-btn-secondary-hover transition">
                    Back to Sections
                </Link>
                <Link :href="route('admin.magazine.articles.index', { section_id: section.id })" class="px-4 py-2 bg-theme-accent text-white rounded-md text-sm font-medium hover:bg-theme-accent-hover transition">
                    View Articles
                </Link>
            </div>
        </div>

        <div class="bg-theme-card border border-theme-border rounded-lg overflow-hidden">
            <div v-if="orderedArticles.length" class="divide-y divide-theme-border">
                <div v-for="(article, index) in orderedArticles" :key="article.id" class="flex items-center gap-4 p-4">
                    <div class="flex flex-col gap-0.5">
                        <button type="button" @click="reorder(index, -1)" :disabled="index === 0" class="text-theme-text-faint hover:text-theme-text-primary disabled:opacity-30 text-xs">&uarr;</button>
                        <button type="button" @click="reorder(index, 1)" :disabled="index === orderedArticles.length - 1" class="text-theme-text-faint hover:text-theme-text-primary disabled:opacity-30 text-xs">&darr;</button>
                    </div>

                    <div class="w-8 shrink-0 text-xs font-medium text-theme-text-faint">
                        {{ index + 1 }}
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <h2 class="font-medium text-theme-text-primary truncate">{{ article.title }}</h2>
                            <span class="text-xs px-2 py-0.5 rounded" :class="statusColors[article.status]">{{ article.status }}</span>
                            <span v-if="article.vertical" class="text-xs text-theme-text-faint">{{ article.vertical.name }}</span>
                        </div>
                        <div class="text-sm text-theme-text-muted mt-1">
                            <span>{{ formatDate(article.published_at) }}</span>
                            <span class="mx-2 text-theme-text-faint">/</span>
                            <span>{{ article.slug }}</span>
                        </div>
                    </div>

                    <Link :href="route('admin.magazine.articles.edit', article.id)" class="text-sm text-theme-link hover:text-theme-link-hover shrink-0">
                        Edit
                    </Link>
                </div>
            </div>

            <div v-else class="text-center py-16 text-theme-text-muted">
                This section does not have any articles yet.
            </div>
        </div>
    </div>
</template>
