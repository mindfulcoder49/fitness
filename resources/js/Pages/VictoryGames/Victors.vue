<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MagazineLayout from '@/Layouts/MagazineLayout.vue';

defineOptions({ layout: MagazineLayout });

defineProps({
    victors: Array,
});

const placementEmoji = { 1: '🥇', 2: '🥈', 3: '🥉' };
</script>

<template>
    <Head title="Our Victors — VibeCode Victory Games" />

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">

        <!-- Breadcrumb -->
        <nav class="text-sm text-theme-text-muted mb-6 flex items-center gap-2">
            <Link :href="route('victory-games.home')" class="hover:text-theme-accent transition">VibeCode Victory Games</Link>
            <span>/</span>
            <span class="text-theme-text-primary">Our Victors</span>
        </nav>

        <div class="mb-10">
            <h1 class="text-3xl font-extrabold text-theme-text-primary">Our Victors</h1>
            <p class="mt-2 text-theme-text-secondary">Builders who competed in the VibeCode Victory Games.</p>
        </div>

        <div v-if="victors.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <Link
                v-for="victor in victors"
                :key="victor.id"
                :href="route('victory-games.victors.show', victor.slug)"
                class="group bg-theme-card border border-theme-border rounded-xl p-5 flex flex-col gap-3 hover:border-theme-accent transition"
            >
                <!-- Avatar -->
                <div class="flex items-center gap-3">
                    <img
                        v-if="victor.avatar_url"
                        :src="victor.avatar_url"
                        :alt="victor.display_name"
                        class="w-12 h-12 rounded-full object-cover"
                    />
                    <div v-else class="w-12 h-12 rounded-full bg-theme-elevated flex items-center justify-center text-lg font-bold text-theme-text-muted">
                        {{ victor.display_name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                        <div class="font-bold text-theme-text-primary group-hover:text-theme-accent transition truncate">
                            {{ victor.display_name }}
                        </div>
                        <div class="text-xs text-theme-text-muted">
                            {{ victor.entries_count }} {{ victor.entries_count === 1 ? 'entry' : 'entries' }}
                        </div>
                    </div>
                </div>

                <!-- Best placement badge -->
                <div v-if="victor.best_placement" class="text-lg">
                    {{ placementEmoji[victor.best_placement] }}
                    <span class="text-xs text-theme-text-muted align-middle">Best finish</span>
                </div>

                <p v-if="victor.bio" class="text-xs text-theme-text-secondary line-clamp-2">{{ victor.bio }}</p>

                <!-- GitHub link -->
                <a
                    v-if="victor.github_url"
                    :href="victor.github_url"
                    target="_blank"
                    rel="noopener"
                    @click.stop
                    class="text-xs text-theme-accent hover:underline mt-auto"
                >
                    GitHub →
                </a>
            </Link>
        </div>

        <div v-else class="text-center py-24">
            <p class="text-theme-text-muted">No victors yet.</p>
        </div>
    </div>
</template>
