<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import MagazineLayout from '@/Layouts/MagazineLayout.vue';

defineOptions({ layout: MagazineLayout });

const props = defineProps({
    competition: Object,
    entries:     Array,
    runs:        Array,
});

const selectedRunId = ref(props.runs[0]?.id ?? null);

const selectedRun = computed(() =>
    props.runs.find(r => r.id === selectedRunId.value) ?? props.runs[0]
);

// Build entry lookup by external_entry_id for bracket display
const entryByExternalId = computed(() => {
    const map = {};
    for (const e of props.entries) map[e.external_entry_id] = e;
    return map;
});

const placementEmoji = { 1: '🥇', 2: '🥈', 3: '🥉' };

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric',
    });
}

// Group matches by round for the selected run
const matchRounds = computed(() => {
    if (!selectedRun.value) return [];
    const rounds = {};
    for (const m of selectedRun.value.matches) {
        if (!rounds[m.round_number]) rounds[m.round_number] = [];
        rounds[m.round_number].push(m);
    }
    return Object.entries(rounds)
        .sort((a, b) => Number(a[0]) - Number(b[0]))
        .map(([round, matches]) => ({ round: Number(round), matches }));
});
</script>

<template>
    <Head :title="`${competition.name} — VibeCode Victory Games`" />

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">

        <!-- Breadcrumb -->
        <nav class="text-sm text-theme-text-muted mb-6 flex items-center gap-2">
            <Link :href="route('victory-games.home')" class="hover:text-theme-accent transition">VibeCode Victory Games</Link>
            <span>/</span>
            <span class="text-theme-text-primary">{{ competition.name }}</span>
        </nav>

        <!-- Header -->
        <div class="mb-10">
            <h1 class="text-3xl sm:text-4xl font-extrabold text-theme-text-primary">{{ competition.name }}</h1>
            <p v-if="competition.description" class="mt-3 text-theme-text-secondary max-w-3xl">
                {{ competition.description }}
            </p>
            <div v-if="competition.held_at" class="mt-2 text-sm text-theme-text-muted">
                {{ formatDate(competition.held_at) }}
            </div>
        </div>

        <!-- Podium (top 3) -->
        <div class="mb-12 grid gap-4 sm:grid-cols-3">
            <template v-for="place in [1,2,3]" :key="place">
                <div v-if="entries.find(e => e.placement === place)" class="bg-theme-card border border-theme-border rounded-xl p-5 flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">{{ placementEmoji[place] }}</span>
                        <span class="text-sm font-bold text-theme-text-muted uppercase tracking-wide">
                            {{ entries.find(e => e.placement === place)?.placement_label }}
                        </span>
                    </div>
                    <template v-for="entry in [entries.find(e => e.placement === place)]" :key="entry.id">
                        <Link
                            v-if="entry.victor"
                            :href="route('victory-games.victors.show', entry.victor.slug)"
                            class="font-bold text-theme-text-primary hover:text-theme-accent transition"
                        >
                            {{ entry.victor.display_name }}
                        </Link>
                        <span class="text-sm text-theme-text-muted truncate">{{ entry.app_hostname }}</span>
                        <Link
                            :href="route('victory-games.runs.show', entry.id)"
                            class="text-xs text-theme-accent hover:underline"
                        >
                            View full run →
                        </Link>
                    </template>
                </div>
            </template>
        </div>

        <!-- Overall Narrative -->
        <div v-if="competition.overall_narrative" class="mb-12 bg-theme-elevated border border-theme-border rounded-xl p-6">
            <h2 class="text-lg font-bold text-theme-text-primary mb-3">Tournament Recap</h2>
            <p class="text-theme-text-secondary leading-relaxed">{{ competition.overall_narrative }}</p>
        </div>

        <!-- All Entries -->
        <div class="mb-12">
            <h2 class="text-xl font-bold text-theme-text-primary mb-4">All Entries</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="entry in entries"
                    :key="entry.id"
                    class="bg-theme-card border border-theme-border rounded-xl p-4 flex flex-col gap-2"
                >
                    <div class="flex items-center justify-between">
                        <span v-if="entry.placement" class="text-lg">{{ placementEmoji[entry.placement] }}</span>
                        <span v-else class="text-xs text-theme-text-faint font-semibold uppercase tracking-wide">Participant</span>
                        <span class="text-xs text-theme-text-muted">{{ entry.step_count }} steps</span>
                    </div>

                    <div class="font-semibold text-theme-text-primary truncate" :title="entry.app_url">
                        {{ entry.app_hostname }}
                    </div>

                    <div v-if="entry.victor" class="text-sm text-theme-text-secondary">
                        <Link :href="route('victory-games.victors.show', entry.victor.slug)" class="hover:text-theme-accent transition">
                            {{ entry.victor.display_name }}
                        </Link>
                    </div>

                    <p v-if="entry.entry_profile?.what_it_does" class="text-xs text-theme-text-muted line-clamp-2 mt-1">
                        {{ entry.entry_profile.what_it_does }}
                    </p>

                    <Link
                        :href="route('victory-games.runs.show', entry.id)"
                        class="mt-auto text-xs font-medium text-theme-accent hover:underline"
                    >
                        View full run →
                    </Link>
                </div>
            </div>
        </div>

        <!-- Bracket Viewer -->
        <div class="mb-12">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-theme-text-primary">Bracket Results</h2>
                <!-- Run selector -->
                <div v-if="runs.length > 1" class="flex items-center gap-2">
                    <span class="text-sm text-theme-text-muted">Run:</span>
                    <div class="flex gap-1">
                        <button
                            v-for="run in runs"
                            :key="run.id"
                            @click="selectedRunId = run.id"
                            class="px-3 py-1 text-xs font-semibold rounded transition"
                            :class="selectedRunId === run.id
                                ? 'bg-theme-accent text-white'
                                : 'bg-theme-elevated text-theme-text-muted hover:text-theme-text-primary border border-theme-border'"
                        >
                            #{{ run.run_number }}
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="selectedRun" class="overflow-x-auto">
                <div class="flex gap-8 min-w-max pb-4">
                    <div v-for="{ round, matches } in matchRounds" :key="round" class="flex flex-col gap-4">
                        <div class="text-xs text-theme-text-muted font-semibold uppercase tracking-wide mb-1">
                            {{ round === Math.max(...matchRounds.map(r => r.round)) ? 'Final' : `Round ${round}` }}
                        </div>
                        <div
                            v-for="match in matches"
                            :key="match.id"
                            class="bg-theme-card border border-theme-border rounded-lg w-56 overflow-hidden"
                        >
                            <div
                                v-for="extId in match.entry_external_ids"
                                :key="extId"
                                class="px-3 py-2 text-xs flex items-center gap-2 border-b border-theme-border last:border-0 transition"
                                :class="match.winner_entry_external_id === extId
                                    ? 'bg-green-500/10 text-theme-text-primary font-semibold'
                                    : 'text-theme-text-muted'"
                            >
                                <span v-if="match.winner_entry_external_id === extId" class="text-green-500 shrink-0">✓</span>
                                <span class="truncate">
                                    {{ entryByExternalId[extId]?.app_hostname ?? `Entry #${extId}` }}
                                </span>
                            </div>
                            <div v-if="match.judge_reasoning" class="px-3 py-2 text-xs text-theme-text-faint italic border-t border-theme-border line-clamp-2">
                                {{ match.judge_reasoning }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>
