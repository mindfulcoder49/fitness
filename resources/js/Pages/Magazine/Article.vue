<script setup>
import { Head } from '@inertiajs/vue3';
import MagazineLayout from '@/Layouts/MagazineLayout.vue';
import SectionBadge from '@/Components/Magazine/SectionBadge.vue';
import TagPill from '@/Components/Magazine/TagPill.vue';
import ContributorCredit from '@/Components/Magazine/ContributorCredit.vue';
import MembersOnlyGate from '@/Components/Magazine/MembersOnlyGate.vue';
import EmbedRenderer from '@/Components/Magazine/EmbedRenderer.vue';

defineOptions({ layout: MagazineLayout });

const props = defineProps({
    article: Object,
    isMembersOnly: Boolean,
});

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric'
    });
}
</script>

<template>
    <Head>
        <title>{{ article.meta_title || article.title }} - {{ $page.props.appName }}</title>
        <meta v-if="article.meta_description" name="description" :content="article.meta_description" />
    </Head>

    <article class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-8">
        <!-- Featured Image -->
        <div v-if="article.featured_image_url" class="mb-8 rounded-xl overflow-hidden">
            <img
                :src="article.featured_image_url"
                :alt="article.title"
                class="w-full aspect-[2/1] object-cover"
            />
        </div>

        <!-- Article Header -->
        <header class="mb-8">
            <div class="flex items-center gap-2 mb-3">
                <SectionBadge v-if="article.section" :section="article.section" />
                <span v-if="article.vertical" class="text-xs text-theme-text-muted">
                    / {{ article.vertical.name }}
                </span>
                <span v-if="article.access === 'members'" class="text-xs px-1.5 py-0.5 bg-theme-warning/20 text-theme-warning rounded">
                    Members Only
                </span>
            </div>

            <h1 class="text-3xl md:text-4xl font-bold text-theme-text-primary leading-tight">
                {{ article.title }}
            </h1>

            <p v-if="article.excerpt" class="mt-4 text-lg text-theme-text-secondary">
                {{ article.excerpt }}
            </p>

            <div class="mt-6 flex flex-wrap items-center gap-4 text-sm text-theme-text-muted">
                <span>{{ formatDate(article.published_at) }}</span>
                <div v-if="article.contributors?.length" class="flex flex-wrap gap-3">
                    <ContributorCredit
                        v-for="contributor in article.contributors"
                        :key="contributor.id"
                        :contributor="contributor"
                    />
                </div>
            </div>
        </header>

        <!-- Article Content -->
        <div v-if="isMembersOnly">
            <MembersOnlyGate>
                <div class="prose prose-lg max-w-none text-theme-text-primary" v-html="article.content"></div>
            </MembersOnlyGate>
        </div>
        <div v-else>
            <EmbedRenderer :html="article.content" />
        </div>

        <!-- Tags -->
        <div v-if="article.tags?.length" class="mt-10 pt-6 border-t border-theme-border">
            <div class="flex flex-wrap gap-2">
                <TagPill v-for="tag in article.tags" :key="tag.id" :tag="tag" />
            </div>
        </div>
    </article>
</template>
