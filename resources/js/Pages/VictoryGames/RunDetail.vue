<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import MagazineLayout from '@/Layouts/MagazineLayout.vue';

defineOptions({ layout: MagazineLayout });

const props = defineProps({
    entry:       Object,
    competition: Object,
    app:         Object,
    victor:      Object,
    steps:       Array,
    canDelete:   Boolean,
    canAssignApp: Boolean,
    assignableApps: Array,
});

const assignForm = useForm({
    app_id: props.app?.id ?? '',
});

function deleteRun(entry) {
    if (!confirm('Delete this run? This cannot be undone.')) return;
    router.delete(route('victory-games.runs.destroy', entry.id));
}

function assignRun() {
    assignForm.post(route('victory-games.runs.assign-app', props.entry.id), {
        preserveScroll: true,
    });
}

watch(() => props.app?.id, (appId) => {
    assignForm.app_id = appId ?? '';
});

const expandedStep = ref(props.steps[0]?.id ?? null);
const activeTab = ref('steps'); // steps | postmortem
const lightboxImage = ref(null);

watch(() => props.steps, (steps) => {
    expandedStep.value = steps[0]?.id ?? null;
}, { immediate: true });

function toggleStep(id) {
    expandedStep.value = expandedStep.value === id ? null : id;
}

function openLightbox(step) {
    if (!step.screenshot_url) return;

    lightboxImage.value = {
        src: step.screenshot_url,
        alt: `Step ${step.step_number} screenshot`,
    };
}

function closeLightbox() {
    lightboxImage.value = null;
}

function handleKeydown(event) {
    if (event.key === 'Escape') {
        closeLightbox();
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});

const actionTypeColor = {
    initialize:    'bg-blue-500/10 text-blue-400',
    navigate:      'bg-purple-500/10 text-purple-400',
    execute_js:    'bg-amber-500/10 text-amber-400',
    save_to_memory:'bg-teal-500/10 text-teal-400',
    finish:        'bg-green-500/10 text-green-400',
    fail:          'bg-red-500/10 text-red-400',
    give_up:       'bg-red-500/10 text-red-400',
};

function typeColor(type) {
    return actionTypeColor[type] ?? 'bg-theme-elevated text-theme-text-muted';
}
</script>

<template>
    <Head :title="`Run: ${entry.app_hostname} — ${competition?.name ?? app?.name ?? 'Standalone'}`" />

    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-10">

        <!-- Breadcrumb -->
        <nav class="text-sm text-theme-text-muted mb-6 flex items-center gap-2 flex-wrap">
            <Link :href="route('victory-games.home')" class="hover:text-theme-accent transition">VibeCode Victory Games</Link>
            <span>/</span>
            <template v-if="competition">
                <Link :href="route('victory-games.competitions.show', competition.slug)" class="hover:text-theme-accent transition">{{ competition.name }}</Link>
            </template>
            <template v-else-if="app">
                <Link :href="route('victory-games.apps.show', app.slug)" class="hover:text-theme-accent transition">{{ app.name }}</Link>
            </template>
            <template v-else>
                <span>Standalone Run</span>
            </template>
            <span>/</span>
            <span class="text-theme-text-primary">{{ entry.app_hostname }}</span>
        </nav>

        <!-- Header -->
        <div class="mb-8 flex items-start gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap mb-1">
                    <span v-if="entry.placement" class="text-2xl">{{ { 1: '🥇', 2: '🥈', 3: '🥉' }[entry.placement] }}</span>
                    <h1 class="text-2xl font-extrabold text-theme-text-primary">{{ entry.app_hostname }}</h1>
                </div>
                <a :href="entry.app_url" target="_blank" rel="noopener" class="text-sm text-theme-accent hover:underline">
                    {{ entry.app_url }}
                </a>
                <div v-if="victor" class="mt-1 text-sm text-theme-text-muted">
                    by <Link :href="route('victory-games.victors.show', victor.slug)" class="text-theme-text-primary hover:text-theme-accent transition">{{ victor.display_name }}</Link>
                </div>
                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                    <span class="px-2 py-0.5 rounded-full bg-theme-elevated text-theme-text-muted">{{ entry.app_mode ?? 'desktop' }}</span>
                    <span v-if="entry.session_provider" class="px-2 py-0.5 rounded-full bg-theme-elevated text-theme-text-muted">{{ entry.session_provider }}</span>
                    <span v-if="entry.session_model" class="px-2 py-0.5 rounded-full bg-theme-elevated text-theme-text-muted">{{ entry.session_model }}</span>
                    <span class="px-2 py-0.5 rounded-full bg-theme-elevated text-theme-text-muted">{{ steps.length }} steps</span>
                </div>
                <p v-if="entry.app_goal" class="mt-3 text-sm text-theme-text-secondary">
                    <span class="font-semibold">Goal:</span> {{ entry.app_goal }}
                </p>
            </div>

            <!-- Delete button -->
            <button
                v-if="canDelete"
                @click="deleteRun(entry)"
                class="shrink-0 px-3 py-1.5 rounded-lg border border-theme-danger/40 text-theme-danger text-xs font-medium hover:bg-theme-danger/10 transition"
            >
                Delete Run
            </button>
        </div>

        <div v-if="canAssignApp" class="mb-8 bg-theme-card border border-theme-border rounded-xl p-5">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-lg font-bold text-theme-text-primary">Attach This Run To An App</h2>
                    <p class="text-sm text-theme-text-muted mt-1">
                        Make this run show up on the right app page and keep your competition history organized.
                    </p>
                </div>
                <Link
                    v-if="victor"
                    :href="route('victory-games.victors.show', victor.slug)"
                    class="text-sm text-theme-accent hover:underline"
                >
                    Back to Victor Profile
                </Link>
            </div>

            <div v-if="assignableApps.length" class="mt-4 flex flex-col sm:flex-row sm:items-center gap-3">
                <select
                    v-model="assignForm.app_id"
                    class="w-full sm:w-80 rounded-lg border border-theme-border bg-theme-input text-theme-text-primary px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-theme-accent-ring"
                >
                    <option value="">— no app —</option>
                    <option v-for="assignableApp in assignableApps" :key="assignableApp.id" :value="assignableApp.id">
                        {{ assignableApp.name }}
                    </option>
                </select>

                <button
                    type="button"
                    @click="assignRun"
                    :disabled="assignForm.processing"
                    class="px-4 py-2 rounded-lg bg-theme-btn-primary text-theme-btn-primary-text text-sm font-semibold hover:bg-theme-btn-primary-hover transition disabled:opacity-50"
                >
                    Save App Link
                </button>
            </div>

            <div v-else class="mt-4 text-sm text-theme-text-muted">
                Create an app from your victor profile first, then come back here to attach this run.
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex border-b border-theme-border mb-6 gap-1">
            <button
                v-for="tab in ['steps', 'postmortem']"
                :key="tab"
                @click="activeTab = tab"
                class="px-4 py-2 text-sm font-medium capitalize transition border-b-2 -mb-px"
                :class="activeTab === tab
                    ? 'border-theme-accent text-theme-accent'
                    : 'border-transparent text-theme-text-muted hover:text-theme-text-primary'"
            >
                {{ tab === 'postmortem' ? 'AI Analysis' : 'Run Steps' }}
            </button>
        </div>

        <!-- Steps tab -->
        <div v-if="activeTab === 'steps'" class="space-y-3">
            <div
                v-for="step in steps"
                :key="step.id"
                class="bg-theme-card border border-theme-border rounded-xl overflow-hidden"
            >
                <!-- Step header — always visible -->
                <button
                    @click="toggleStep(step.id)"
                    class="w-full text-left px-4 py-3 flex items-center gap-3 hover:bg-theme-elevated/50 transition"
                >
                    <span class="text-xs font-mono text-theme-text-faint w-6 shrink-0">{{ step.step_number }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full font-semibold shrink-0" :class="typeColor(step.action_type)">
                        {{ step.action_type }}
                    </span>
                    <span class="flex-1 text-sm text-theme-text-secondary truncate">
                        {{ step.intent || step.page_url || '—' }}
                    </span>
                    <span v-if="!step.success" class="text-xs text-red-400 shrink-0">✗ failed</span>
                    <svg class="w-4 h-4 text-theme-text-faint shrink-0 transition-transform" :class="expandedStep === step.id ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Expanded step detail -->
                <div v-if="expandedStep === step.id" class="border-t border-theme-border">
                    <!-- Screenshot -->
                    <div v-if="step.screenshot_url" class="border-b border-theme-border">
                        <img
                            :src="step.screenshot_url"
                            :alt="`Step ${step.step_number} screenshot`"
                            class="w-full max-h-[600px] object-top object-cover cursor-zoom-in"
                            loading="lazy"
                            @click="openLightbox(step)"
                        />
                    </div>

                    <div class="p-4 space-y-3 text-sm">
                        <div v-if="step.page_url" class="text-xs text-theme-text-muted font-mono break-all">
                            {{ step.page_url }}
                        </div>

                        <div v-if="step.intent">
                            <div class="text-xs font-semibold text-theme-text-muted uppercase tracking-wide mb-1">Intent</div>
                            <p class="text-theme-text-secondary">{{ step.intent }}</p>
                        </div>

                        <div v-if="step.reasoning">
                            <div class="text-xs font-semibold text-theme-text-muted uppercase tracking-wide mb-1">Reasoning</div>
                            <p class="text-theme-text-secondary">{{ step.reasoning }}</p>
                        </div>

                        <div v-if="step.action_result">
                            <div class="text-xs font-semibold text-theme-text-muted uppercase tracking-wide mb-1">Result</div>
                            <pre class="text-xs bg-theme-elevated rounded p-2 overflow-x-auto text-theme-text-muted whitespace-pre-wrap">{{ typeof step.action_result === 'string' ? step.action_result : JSON.stringify(step.action_result, null, 2) }}</pre>
                        </div>

                        <div v-if="step.error_message" class="text-xs text-red-400">
                            Error: {{ step.error_message }}
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!steps.length" class="text-center py-12 text-theme-text-muted">
                No steps recorded for this run.
            </div>
        </div>

        <!-- Postmortem tab -->
        <div v-if="activeTab === 'postmortem'" class="space-y-6">
            <!-- Entry profile (from recap) -->
            <div v-if="entry.entry_profile && Object.keys(entry.entry_profile).length" class="bg-theme-card border border-theme-border rounded-xl p-6">
                <h2 class="text-lg font-bold text-theme-text-primary mb-4">Competition Profile</h2>
                <div class="space-y-4">
                    <div v-for="[key, label] in [['what_it_does','What It Does'],['human_verdict','Human Verdict'],['agent_limitations','AI Testing Limitations'],['profile','Competition Performance']]" :key="key">
                        <div v-if="entry.entry_profile[key]">
                            <div class="text-xs font-semibold uppercase tracking-wide text-theme-text-muted mb-1">{{ label }}</div>
                            <p class="text-sm text-theme-text-secondary">{{ entry.entry_profile[key] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="entry.postmortem">
                <div v-if="entry.postmortem.run_analysis" class="bg-theme-card border border-theme-border rounded-xl p-6">
                    <h2 class="text-lg font-bold text-theme-text-primary mb-3">Run Analysis</h2>
                    <pre class="text-sm text-theme-text-secondary whitespace-pre-wrap">{{ entry.postmortem.run_analysis }}</pre>
                </div>

                <div v-if="entry.postmortem.recommendations" class="bg-theme-card border border-theme-border rounded-xl p-6 mt-4">
                    <h2 class="text-lg font-bold text-theme-text-primary mb-3">Recommendations</h2>
                    <pre class="text-sm text-theme-text-secondary whitespace-pre-wrap">{{ entry.postmortem.recommendations }}</pre>
                </div>

                <div v-if="entry.postmortem.html_analysis" class="bg-theme-card border border-theme-border rounded-xl p-6 mt-4">
                    <h2 class="text-lg font-bold text-theme-text-primary mb-3">HTML / Accessibility Analysis</h2>
                    <pre class="text-sm text-theme-text-secondary whitespace-pre-wrap">{{ entry.postmortem.html_analysis }}</pre>
                </div>
            </div>

            <div v-if="!entry.postmortem && !(entry.entry_profile && Object.keys(entry.entry_profile).length)" class="text-center py-12 text-theme-text-muted">
                No analysis available for this entry.
            </div>
        </div>

        <div
            v-if="lightboxImage"
            class="fixed inset-0 z-50 bg-black/85 px-4 py-6 sm:px-8"
            @click="closeLightbox"
        >
            <button
                type="button"
                class="absolute right-4 top-4 rounded-lg border border-white/20 bg-black/40 px-3 py-1.5 text-sm text-white hover:bg-black/60 transition"
                @click.stop="closeLightbox"
            >
                Close
            </button>
            <div class="flex h-full items-center justify-center">
                <img
                    :src="lightboxImage.src"
                    :alt="lightboxImage.alt"
                    class="max-h-full max-w-full object-contain"
                    @click.stop
                />
            </div>
        </div>
    </div>
</template>
