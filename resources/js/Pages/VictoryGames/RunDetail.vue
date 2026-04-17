<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    entry: Object,
    competition: Object,
    app: Object,
    victor: Object,
    steps: Array,
    logs: Array,
    canDelete: Boolean,
    canAssignApp: Boolean,
    canStop: Boolean,
    assignableApps: Array,
});

const liveEntry = ref(props.entry);
const liveCompetition = ref(props.competition);
const liveApp = ref(props.app);
const liveVictor = ref(props.victor);
const liveSteps = ref(props.steps ?? []);
const liveLogs = ref(props.logs ?? []);
const liveCanDelete = ref(props.canDelete);
const liveCanAssignApp = ref(props.canAssignApp);
const liveCanStop = ref(props.canStop);
const liveAssignableApps = ref(props.assignableApps ?? []);

watch(() => props.entry, (entry) => {
    liveEntry.value = entry;
}, { immediate: true });

watch(() => props.competition, (competition) => {
    liveCompetition.value = competition;
}, { immediate: true });

watch(() => props.app, (app) => {
    liveApp.value = app;
}, { immediate: true });

watch(() => props.victor, (victor) => {
    liveVictor.value = victor;
}, { immediate: true });

watch(() => props.steps, (steps) => {
    liveSteps.value = steps ?? [];
}, { immediate: true });

watch(() => props.logs, (logs) => {
    liveLogs.value = logs ?? [];
}, { immediate: true });

watch(() => props.canDelete, (canDelete) => {
    liveCanDelete.value = canDelete;
}, { immediate: true });

watch(() => props.canAssignApp, (canAssignApp) => {
    liveCanAssignApp.value = canAssignApp;
}, { immediate: true });

watch(() => props.canStop, (canStop) => {
    liveCanStop.value = canStop;
}, { immediate: true });

watch(() => props.assignableApps, (assignableApps) => {
    liveAssignableApps.value = assignableApps ?? [];
}, { immediate: true });

const assignForm = useForm({
    app_id: props.app?.id ?? '',
});

const stopForm = useForm({});
const expandedStep = ref(null);
const activeTab = ref('steps');
const lightboxImage = ref(null);
const pollingReady = ref(false);

watch(() => liveApp.value?.id, (appId) => {
    assignForm.app_id = appId ?? '';
}, { immediate: true });

watch(liveSteps, (steps) => {
    if (!steps.length) {
        expandedStep.value = null;
        return;
    }

    if (!steps.some((step) => step.id === expandedStep.value)) {
        expandedStep.value = steps[0].id;
    }
}, { immediate: true });

const isActiveRun = computed(() => ['queued', 'running', 'analyzing'].includes(liveEntry.value?.session_status));

const actionTypeColor = {
    initialize: 'bg-blue-500/10 text-blue-400',
    navigate: 'bg-purple-500/10 text-purple-400',
    execute_js: 'bg-amber-500/10 text-amber-400',
    save_to_memory: 'bg-teal-500/10 text-teal-400',
    finish: 'bg-green-500/10 text-green-400',
    fail: 'bg-red-500/10 text-red-400',
    give_up: 'bg-red-500/10 text-red-400',
};

const sessionStatusColor = {
    queued: 'bg-slate-500/10 text-slate-300',
    running: 'bg-blue-500/10 text-blue-300',
    analyzing: 'bg-amber-500/10 text-amber-300',
    completed: 'bg-green-500/10 text-green-300',
    failed: 'bg-red-500/10 text-red-300',
    stopped: 'bg-zinc-500/10 text-zinc-300',
    loop_detected: 'bg-orange-500/10 text-orange-300',
};

function deleteRun() {
    if (!confirm('Delete this run? This cannot be undone.')) return;

    router.delete(route('victory-games.runs.destroy', liveEntry.value.id));
}

function assignRun() {
    assignForm.post(route('victory-games.runs.assign-app', liveEntry.value.id), {
        preserveScroll: true,
    });
}

function stopRun() {
    if (!confirm('Stop this active native run?')) return;

    stopForm.post(route('victory-games.runs.stop', liveEntry.value.id), {
        preserveScroll: true,
    });
}

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

function typeColor(type) {
    return actionTypeColor[type] ?? 'bg-theme-elevated text-theme-text-muted';
}

function statusColor(status) {
    return sessionStatusColor[status] ?? 'bg-theme-elevated text-theme-text-muted';
}

function formatStatus(status) {
    return status ? status.replaceAll('_', ' ') : 'unknown';
}

function formatDateTime(value) {
    if (!value) return '—';

    return new Date(value).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

function formatLogDetails(details) {
    if (!details) return '';

    return typeof details === 'string' ? details : JSON.stringify(details, null, 2);
}

let pollHandle = null;

async function pollStatus() {
    try {
        const response = await fetch(route('victory-games.runs.status', props.entry.id), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`Status poll failed with ${response.status}`);
        }

        const payload = await response.json();

        liveEntry.value = payload.entry;
        liveCompetition.value = payload.competition;
        liveApp.value = payload.app;
        liveVictor.value = payload.victor;
        liveSteps.value = payload.steps ?? [];
        liveLogs.value = payload.logs ?? [];
        liveCanDelete.value = Boolean(payload.canDelete);
        liveCanAssignApp.value = Boolean(payload.canAssignApp);
        liveCanStop.value = Boolean(payload.canStop);
        liveAssignableApps.value = payload.assignableApps ?? [];
    } catch (_) {
        // Ignore transient polling failures and keep the last known state on screen.
    }

    if (!['queued', 'running', 'analyzing'].includes(liveEntry.value?.session_status)) {
        stopPolling();
    }
}

function stopPolling() {
    if (pollHandle !== null) {
        window.clearInterval(pollHandle);
        pollHandle = null;
    }
}

function startPolling() {
    if (pollHandle !== null || !isActiveRun.value) {
        return;
    }

    pollHandle = window.setInterval(() => {
        pollStatus();
    }, (liveEntry.value?.poll_interval_seconds ?? 4) * 1000);
}

watch(isActiveRun, (active) => {
    if (!pollingReady.value) {
        return;
    }

    if (active) {
        pollStatus();
        startPolling();
        return;
    }

    stopPolling();
}, { immediate: false });

onMounted(() => {
    pollingReady.value = true;
    window.addEventListener('keydown', handleKeydown);

    if (isActiveRun.value) {
        pollStatus();
        startPolling();
    }
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    stopPolling();
});
</script>

<template>
    <Head :title="`Run: ${liveEntry.app_hostname} — ${liveCompetition?.name ?? liveApp?.name ?? 'Standalone'}`" />

    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">

        <nav class="mb-6 flex flex-wrap items-center gap-2 text-sm text-theme-text-muted">
            <Link :href="route('victory-games.home')" class="transition hover:text-theme-accent">VibeCode Victory Games</Link>
            <span>/</span>
            <template v-if="liveCompetition">
                <Link :href="route('victory-games.competitions.show', liveCompetition.slug)" class="transition hover:text-theme-accent">{{ liveCompetition.name }}</Link>
            </template>
            <template v-else-if="liveApp">
                <Link :href="route('victory-games.apps.show', liveApp.slug)" class="transition hover:text-theme-accent">{{ liveApp.name }}</Link>
            </template>
            <template v-else>
                <span>Standalone Run</span>
            </template>
            <span>/</span>
            <span class="text-theme-text-primary">{{ liveEntry.app_hostname }}</span>
        </nav>

        <div class="mb-8 flex items-start gap-4">
            <div class="min-w-0 flex-1">
                <div class="mb-1 flex flex-wrap items-center gap-2">
                    <span v-if="liveEntry.placement" class="text-2xl">{{ { 1: '🥇', 2: '🥈', 3: '🥉' }[liveEntry.placement] }}</span>
                    <h1 class="text-2xl font-extrabold text-theme-text-primary">{{ liveEntry.app_hostname }}</h1>
                </div>

                <a :href="liveEntry.app_url" target="_blank" rel="noopener" class="text-sm text-theme-accent hover:underline">
                    {{ liveEntry.app_url }}
                </a>

                <div v-if="liveVictor" class="mt-1 text-sm text-theme-text-muted">
                    by
                    <Link :href="route('victory-games.victors.show', liveVictor.slug)" class="text-theme-text-primary transition hover:text-theme-accent">
                        {{ liveVictor.display_name }}
                    </Link>
                </div>

                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                    <span class="rounded-full bg-theme-elevated px-2 py-0.5 text-theme-text-muted">{{ liveEntry.run_origin === 'native' ? 'Native AIUX' : 'AIUXTester import' }}</span>
                    <span class="rounded-full bg-theme-elevated px-2 py-0.5 text-theme-text-muted">{{ liveEntry.app_mode ?? 'desktop' }}</span>
                    <span v-if="liveEntry.session_provider" class="rounded-full bg-theme-elevated px-2 py-0.5 text-theme-text-muted">{{ liveEntry.session_provider }}</span>
                    <span v-if="liveEntry.session_model" class="rounded-full bg-theme-elevated px-2 py-0.5 text-theme-text-muted">{{ liveEntry.session_model }}</span>
                    <span class="rounded-full px-2 py-0.5 capitalize" :class="statusColor(liveEntry.session_status)">
                        {{ formatStatus(liveEntry.session_status) }}
                    </span>
                    <span class="rounded-full bg-theme-elevated px-2 py-0.5 text-theme-text-muted">{{ liveSteps.length }} steps</span>
                </div>

                <p v-if="liveEntry.app_goal" class="mt-3 text-sm text-theme-text-secondary">
                    <span class="font-semibold">Goal:</span> {{ liveEntry.app_goal }}
                </p>
            </div>

            <div class="flex shrink-0 flex-col gap-2">
                <button
                    v-if="liveCanStop && isActiveRun"
                    type="button"
                    @click="stopRun"
                    :disabled="stopForm.processing"
                    class="rounded-lg border border-theme-danger/40 px-3 py-1.5 text-xs font-medium text-theme-danger transition hover:bg-theme-danger/10 disabled:opacity-50"
                >
                    Stop Run
                </button>

                <button
                    v-if="liveCanDelete && !isActiveRun"
                    type="button"
                    @click="deleteRun"
                    class="rounded-lg border border-theme-danger/40 px-3 py-1.5 text-xs font-medium text-theme-danger transition hover:bg-theme-danger/10"
                >
                    Delete Run
                </button>
            </div>
        </div>

        <div
            v-if="liveEntry.run_origin === 'native'"
            class="mb-8 rounded-xl border border-theme-border bg-theme-card p-5"
        >
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-theme-text-primary">Native Run Status</h2>
                    <p class="mt-1 text-sm text-theme-text-secondary">
                        This run is executed by a queued worker using Playwright PHP and the Laravel AI SDK.
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                        <span class="rounded-full px-2 py-0.5 capitalize" :class="statusColor(liveEntry.session_status)">
                            {{ formatStatus(liveEntry.session_status) }}
                        </span>
                        <span class="rounded-full bg-theme-elevated px-2 py-0.5 text-theme-text-muted">
                            Polling every {{ liveEntry.poll_interval_seconds ?? 4 }}s
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-theme-text-muted">Started</div>
                        <div class="mt-1 text-theme-text-secondary">{{ formatDateTime(liveEntry.started_at) }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-theme-text-muted">Completed</div>
                        <div class="mt-1 text-theme-text-secondary">{{ formatDateTime(liveEntry.completed_at) }}</div>
                    </div>
                </div>
            </div>

            <div v-if="liveEntry.end_reason" class="mt-4 rounded-lg bg-theme-elevated px-4 py-3 text-sm text-theme-text-secondary">
                {{ liveEntry.end_reason }}
            </div>
        </div>

        <div v-if="liveCanAssignApp" class="mb-8 rounded-xl border border-theme-border bg-theme-card p-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-theme-text-primary">Attach This Run To An App</h2>
                    <p class="mt-1 text-sm text-theme-text-muted">
                        Make this run show up on the right app page and keep your history organized.
                    </p>
                </div>
                <Link
                    v-if="liveVictor"
                    :href="route('victory-games.victors.show', liveVictor.slug)"
                    class="text-sm text-theme-accent hover:underline"
                >
                    Back to Victor Profile
                </Link>
            </div>

            <div v-if="liveAssignableApps.length" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                <select
                    v-model="assignForm.app_id"
                    class="w-full rounded-lg border border-theme-border bg-theme-input px-3 py-2 text-sm text-theme-text-primary focus:outline-none focus:ring-2 focus:ring-theme-accent-ring sm:w-80"
                >
                    <option value="">— no app —</option>
                    <option v-for="assignableApp in liveAssignableApps" :key="assignableApp.id" :value="assignableApp.id">
                        {{ assignableApp.name }}
                    </option>
                </select>

                <button
                    type="button"
                    @click="assignRun"
                    :disabled="assignForm.processing"
                    class="rounded-lg bg-theme-btn-primary px-4 py-2 text-sm font-semibold text-theme-btn-primary-text transition hover:bg-theme-btn-primary-hover disabled:opacity-50"
                >
                    Save App Link
                </button>
            </div>

            <div v-else class="mt-4 text-sm text-theme-text-muted">
                Create an app from your victor profile first, then come back here to attach this run.
            </div>
        </div>

        <div class="mb-6 flex gap-1 border-b border-theme-border">
            <button
                v-for="tab in [
                    { key: 'steps', label: 'Run Steps' },
                    { key: 'logs', label: 'Logs' },
                    { key: 'postmortem', label: 'AI Analysis' },
                ]"
                :key="tab.key"
                type="button"
                @click="activeTab = tab.key"
                class="border-b-2 px-4 py-2 text-sm font-medium transition -mb-px"
                :class="activeTab === tab.key
                    ? 'border-theme-accent text-theme-accent'
                    : 'border-transparent text-theme-text-muted hover:text-theme-text-primary'"
            >
                {{ tab.label }}
            </button>
        </div>

        <div v-if="activeTab === 'steps'" class="space-y-3">
            <div
                v-for="step in liveSteps"
                :key="step.id"
                class="overflow-hidden rounded-xl border border-theme-border bg-theme-card"
            >
                <button
                    type="button"
                    @click="toggleStep(step.id)"
                    class="flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-theme-elevated/50"
                >
                    <span class="w-6 shrink-0 text-xs font-mono text-theme-text-faint">{{ step.step_number }}</span>
                    <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold" :class="typeColor(step.action_type)">
                        {{ step.action_type }}
                    </span>
                    <span class="flex-1 truncate text-sm text-theme-text-secondary">
                        {{ step.intent || step.page_url || '—' }}
                    </span>
                    <span v-if="!step.success" class="shrink-0 text-xs text-red-400">✗ failed</span>
                    <svg class="h-4 w-4 shrink-0 text-theme-text-faint transition-transform" :class="expandedStep === step.id ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div v-if="expandedStep === step.id" class="border-t border-theme-border">
                    <div v-if="step.screenshot_url" class="border-b border-theme-border">
                        <img
                            :src="step.screenshot_url"
                            :alt="`Step ${step.step_number} screenshot`"
                            class="max-h-[600px] w-full cursor-zoom-in object-cover object-top"
                            loading="lazy"
                            @click="openLightbox(step)"
                        />
                    </div>

                    <div class="space-y-3 p-4 text-sm">
                        <div v-if="step.page_url" class="break-all font-mono text-xs text-theme-text-muted">
                            {{ step.page_url }}
                        </div>

                        <div v-if="step.intent">
                            <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-theme-text-muted">Intent</div>
                            <p class="text-theme-text-secondary">{{ step.intent }}</p>
                        </div>

                        <div v-if="step.reasoning">
                            <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-theme-text-muted">Reasoning</div>
                            <p class="text-theme-text-secondary">{{ step.reasoning }}</p>
                        </div>

                        <div v-if="step.action_result">
                            <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-theme-text-muted">Result</div>
                            <pre class="overflow-x-auto whitespace-pre-wrap rounded bg-theme-elevated p-2 text-xs text-theme-text-muted">{{ typeof step.action_result === 'string' ? step.action_result : JSON.stringify(step.action_result, null, 2) }}</pre>
                        </div>

                        <div v-if="step.error_message" class="text-xs text-red-400">
                            Error: {{ step.error_message }}
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!liveSteps.length" class="py-12 text-center text-theme-text-muted">
                No steps recorded for this run yet.
            </div>
        </div>

        <div v-if="activeTab === 'logs'" class="space-y-3">
            <div
                v-for="log in liveLogs"
                :key="log.id"
                class="rounded-xl border border-theme-border bg-theme-card p-4"
            >
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="rounded-full bg-theme-elevated px-2 py-0.5 uppercase text-theme-text-muted">{{ log.level }}</span>
                    <span v-if="log.step_number !== null" class="rounded-full bg-theme-elevated px-2 py-0.5 text-theme-text-muted">Step {{ log.step_number }}</span>
                    <span class="text-theme-text-faint">{{ formatDateTime(log.created_at) }}</span>
                </div>

                <div class="mt-2 text-sm font-medium text-theme-text-primary">{{ log.message }}</div>
                <pre v-if="log.details" class="mt-3 overflow-x-auto whitespace-pre-wrap rounded bg-theme-elevated p-3 text-xs text-theme-text-muted">{{ formatLogDetails(log.details) }}</pre>
            </div>

            <div v-if="!liveLogs.length" class="py-12 text-center text-theme-text-muted">
                No logs recorded for this run yet.
            </div>
        </div>

        <div v-if="activeTab === 'postmortem'" class="space-y-6">
            <div v-if="liveEntry.entry_profile && Object.keys(liveEntry.entry_profile).length" class="rounded-xl border border-theme-border bg-theme-card p-6">
                <h2 class="mb-4 text-lg font-bold text-theme-text-primary">Competition Profile</h2>
                <div class="space-y-4">
                    <div v-for="[key, label] in [['what_it_does', 'What It Does'], ['human_verdict', 'Human Verdict'], ['agent_limitations', 'AI Testing Limitations'], ['profile', 'Competition Performance']]" :key="key">
                        <div v-if="liveEntry.entry_profile[key]">
                            <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-theme-text-muted">{{ label }}</div>
                            <p class="text-sm text-theme-text-secondary">{{ liveEntry.entry_profile[key] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="liveEntry.postmortem">
                <div v-if="liveEntry.postmortem.run_analysis" class="rounded-xl border border-theme-border bg-theme-card p-6">
                    <h2 class="mb-3 text-lg font-bold text-theme-text-primary">Run Analysis</h2>
                    <pre class="whitespace-pre-wrap text-sm text-theme-text-secondary">{{ liveEntry.postmortem.run_analysis }}</pre>
                </div>

                <div v-if="liveEntry.postmortem.recommendations" class="mt-4 rounded-xl border border-theme-border bg-theme-card p-6">
                    <h2 class="mb-3 text-lg font-bold text-theme-text-primary">Recommendations</h2>
                    <pre class="whitespace-pre-wrap text-sm text-theme-text-secondary">{{ liveEntry.postmortem.recommendations }}</pre>
                </div>

                <div v-if="liveEntry.postmortem.html_analysis" class="mt-4 rounded-xl border border-theme-border bg-theme-card p-6">
                    <h2 class="mb-3 text-lg font-bold text-theme-text-primary">HTML / Accessibility Analysis</h2>
                    <pre class="whitespace-pre-wrap text-sm text-theme-text-secondary">{{ liveEntry.postmortem.html_analysis }}</pre>
                </div>
            </div>

            <div v-if="isActiveRun && !(liveEntry.postmortem?.run_analysis || liveEntry.postmortem?.html_analysis || liveEntry.postmortem?.recommendations)" class="py-12 text-center text-theme-text-muted">
                AI analysis runs after the queued worker finishes the browser session.
            </div>

            <div v-if="!isActiveRun && !liveEntry.postmortem && !(liveEntry.entry_profile && Object.keys(liveEntry.entry_profile).length)" class="py-12 text-center text-theme-text-muted">
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
                class="absolute right-4 top-4 rounded-lg border border-white/20 bg-black/40 px-3 py-1.5 text-sm text-white transition hover:bg-black/60"
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
