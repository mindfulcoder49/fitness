<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

defineProps({
    competitions: Array,
});

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric',
    });
}
</script>

<template>
    <Head title="VibeCode Victory Games" />

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">

        <!-- Hero -->
        <div class="text-center mb-16">
            <div class="text-5xl mb-4">🏅</div>
            <h1 class="text-4xl sm:text-5xl font-extrabold text-theme-text-primary tracking-tight">
                VibeCode Victory Games
            </h1>
            <p class="mt-4 text-lg text-theme-text-secondary max-w-2xl mx-auto">
                AI-judged UX testing competitions. Apps go head-to-head in single-elimination brackets,
                evaluated by an LLM on real usability outcomes.
            </p>
            <div class="mt-6 flex justify-center gap-4">
                <Link :href="route('victory-games.victors')" class="px-5 py-2.5 rounded-lg bg-theme-btn-primary text-theme-btn-primary-text font-semibold hover:bg-theme-btn-primary-hover transition text-sm">
                    Meet the Victors
                </Link>
                <Link :href="route('victory-games.certificates')" class="px-5 py-2.5 rounded-lg border border-theme-border text-theme-text-secondary font-semibold hover:text-theme-text-primary hover:border-theme-accent transition text-sm">
                    My Certificates
                </Link>
            </div>
        </div>

        <!-- Competitions -->
        <div v-if="competitions.length" class="space-y-6">
            <h2 class="text-2xl font-bold text-theme-text-primary">Competitions</h2>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="comp in competitions"
                    :key="comp.id"
                    :href="route('victory-games.competitions.show', comp.slug)"
                    class="group block bg-theme-card border border-theme-border rounded-xl overflow-hidden hover:border-theme-accent transition"
                >
                    <!-- Champion banner -->
                    <div v-if="comp.champion" class="bg-gradient-to-r from-yellow-500/10 to-amber-500/10 border-b border-theme-border px-5 py-3 flex items-center gap-3">
                        <div class="text-xl">🥇</div>
                        <div class="min-w-0">
                            <div class="text-xs text-theme-text-muted uppercase tracking-wide font-semibold">Champion</div>
                            <div class="text-sm font-bold text-theme-text-primary truncate">
                                {{ comp.champion.victor?.display_name || comp.champion.app_hostname }}
                            </div>
                            <div class="text-xs text-theme-text-muted truncate">{{ comp.champion.app_hostname }}</div>
                        </div>
                    </div>
                    <div v-else class="h-2 bg-gradient-to-r from-theme-accent/30 to-theme-accent/10" />

                    <div class="p-5">
                        <h3 class="text-lg font-bold text-theme-text-primary group-hover:text-theme-accent transition line-clamp-2">
                            {{ comp.name }}
                        </h3>
                        <p v-if="comp.description" class="mt-2 text-sm text-theme-text-secondary line-clamp-2">
                            {{ comp.description }}
                        </p>
                        <div class="mt-4 flex items-center justify-between text-xs text-theme-text-muted">
                            <span>{{ comp.entries_count }} {{ comp.entries_count === 1 ? 'entry' : 'entries' }}</span>
                            <span v-if="comp.held_at">{{ formatDate(comp.held_at) }}</span>
                        </div>
                    </div>
                </Link>
            </div>
        </div>

        <div v-else class="text-center py-24">
            <div class="text-5xl mb-4">🏗️</div>
            <p class="text-theme-text-muted text-lg">No competitions yet. Check back soon!</p>
        </div>
    </div>
</template>
