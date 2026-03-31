<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MagazineLayout from '@/Layouts/MagazineLayout.vue';
import ArticleCard from '@/Components/Magazine/ArticleCard.vue';
import SectionBadge from '@/Components/Magazine/SectionBadge.vue';

defineOptions({ layout: MagazineLayout });

defineProps({
    featured: Object,
    sections: Array,
});

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric'
    });
}
</script>

<template>
    <Head :title="$page.props.appName" />

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <!-- Hero Featured Article -->
        <div v-if="featured" class="mb-16">
            <Link :href="route('magazine.article', featured.slug)" class="group block">
                <div class="grid md:grid-cols-2 gap-8 bg-theme-card border border-theme-border rounded-xl overflow-hidden">
                    <div v-if="featured.featured_image_url" class="aspect-[16/10] md:aspect-auto overflow-hidden">
                        <img
                            :src="featured.featured_image_url"
                            :alt="featured.title"
                            class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                        />
                    </div>
                    <div v-else class="aspect-[16/10] md:aspect-auto bg-theme-elevated flex items-center justify-center">
                        <svg class="w-20 h-20 text-theme-text-faint" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                        </svg>
                    </div>
                    <div class="p-6 md:p-8 flex flex-col justify-center">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-theme-accent">Featured</span>
                            <SectionBadge v-if="featured.section" :section="featured.section" />
                        </div>
                        <h1 class="text-2xl md:text-3xl font-bold text-theme-text-primary group-hover:text-theme-accent transition">
                            {{ featured.title }}
                        </h1>
                        <p v-if="featured.excerpt" class="mt-3 text-theme-text-secondary line-clamp-3">
                            {{ featured.excerpt }}
                        </p>
                        <div class="mt-4 flex items-center gap-4 text-sm text-theme-text-muted">
                            <span v-if="featured.contributors?.length">
                                {{ featured.contributors.map(c => c.name).join(', ') }}
                            </span>
                            <span>{{ formatDate(featured.published_at) }}</span>
                        </div>
                    </div>
                </div>
            </Link>
        </div>

        <!-- Sections with Latest Articles -->
        <div v-for="section in sections" :key="section.id" class="mb-12">
            <div v-if="section.articles?.length" class="mb-12">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-theme-text-primary">{{ section.name }}</h2>
                    <Link
                        :href="route('magazine.section', section.slug)"
                        class="text-sm font-medium text-theme-link hover:text-theme-link-hover transition"
                    >
                        View all &rarr;
                    </Link>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <ArticleCard v-for="article in section.articles" :key="article.id" :article="article" />
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!featured && (!sections || sections.every(s => !s.articles?.length))" class="text-center py-24">
            <svg class="w-16 h-16 mx-auto text-theme-text-faint mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
            </svg>
            <h2 class="text-xl font-semibold text-theme-text-primary mb-2">Coming Soon</h2>
            <p class="text-theme-text-muted">Articles are being prepared. Check back soon.</p>
        </div>
    </div>
</template>
