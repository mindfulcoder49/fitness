<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import MagazineLayout from '@/Layouts/MagazineLayout.vue';

defineOptions({ layout: MagazineLayout });

const props = defineProps({
    app: Object,
    entries: Array,
    canEdit: Boolean,
    nativeRun: Object,
});

const editing = ref(false);
const addingMember = ref(false);
const showingRunForm = ref(Boolean(props.nativeRun?.canStart && props.entries.length === 0));
const runComposer = ref(null);

const editForm = useForm({
    _method: 'patch',
    name: props.app.name,
    description: props.app.description ?? '',
    current_url: props.app.current_url ?? '',
});

const memberForm = useForm({ victor_slug: '' });
const providerOptions = computed(() => props.nativeRun?.providers ?? []);

function createRunFormDefaults(overrides = {}) {
    const providerFallback = props.nativeRun?.defaults?.provider ?? providerOptions.value[0]?.key ?? '';
    const requestedProvider = overrides.provider || '';
    const provider = providerOptions.value.some((option) => option.key === requestedProvider)
        ? requestedProvider
        : providerFallback;
    const selectedProvider = providerOptions.value.find((option) => option.key === provider);
    const providerStayedTheSame = provider === requestedProvider;

    return {
        goal: '',
        start_url: props.nativeRun?.defaults?.start_url ?? props.app.current_url ?? '',
        mode: props.nativeRun?.defaults?.mode ?? 'desktop',
        provider,
        model: providerStayedTheSame
            ? (overrides.model || selectedProvider?.default_model || props.nativeRun?.defaults?.model || '')
            : (selectedProvider?.default_model || props.nativeRun?.defaults?.model || ''),
        max_steps: props.nativeRun?.defaults?.max_steps ?? 8,
        ...overrides,
    };
}

const runForm = useForm(createRunFormDefaults());

function submitEdit() {
    editForm.post(route('victory-games.apps.update', props.app.slug), {
        onSuccess: () => { editing.value = false; },
    });
}

function submitAddMember() {
    memberForm.post(route('victory-games.apps.members.add', props.app.slug), {
        onSuccess: () => { addingMember.value = false; memberForm.reset(); },
    });
}

function removeMember(victorSlug) {
    if (!confirm('Remove this team member?')) return;
    useForm({}).delete(route('victory-games.apps.members.remove', [props.app.slug, victorSlug]));
}

watch(() => runForm.provider, (providerKey) => {
    const selectedProvider = providerOptions.value.find((provider) => provider.key === providerKey);

    if (selectedProvider) {
        runForm.model = selectedProvider.default_model ?? '';
    }
}, { immediate: true });

function submitRun() {
    runForm.post(route('victory-games.apps.runs.store', props.app.slug), {
        preserveScroll: true,
    });
}

function deleteRun(entry) {
    if (!confirm('Delete this run? This cannot be undone.')) return;

    router.delete(route('victory-games.runs.destroy', entry.id), {
        preserveScroll: true,
    });
}

async function reuseRunConfig(entry) {
    const nextValues = createRunFormDefaults(entry.reuse_config ?? {});

    Object.assign(runForm, nextValues);
    runForm.clearErrors();
    showingRunForm.value = true;

    await nextTick();
    runComposer.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

const placementEmoji = { 1: '🥇', 2: '🥈', 3: '🥉' };
const statusColors = {
    queued: 'bg-slate-500/10 text-slate-300',
    running: 'bg-blue-500/10 text-blue-300',
    analyzing: 'bg-amber-500/10 text-amber-300',
    completed: 'bg-green-500/10 text-green-300',
    failed: 'bg-red-500/10 text-red-300',
    stopped: 'bg-zinc-500/10 text-zinc-300',
    loop_detected: 'bg-orange-500/10 text-orange-300',
};

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function formatStatus(status) {
    return status ? status.replaceAll('_', ' ') : 'unknown';
}

function statusBadgeClass(status) {
    return statusColors[status] ?? 'bg-theme-elevated text-theme-text-muted';
}
</script>

<template>
    <Head :title="`${app.name} — VibeCode Victory Games`" />

    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-10">

        <!-- Breadcrumb -->
        <nav class="text-sm text-theme-text-muted mb-6 flex items-center gap-2">
            <Link :href="route('victory-games.home')" class="hover:text-theme-accent transition">VibeCode Victory Games</Link>
            <span>/</span>
            <span class="text-theme-text-primary">{{ app.name }}</span>
        </nav>

        <!-- App header -->
        <div v-if="!editing" class="mb-8">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h1 class="text-3xl font-extrabold text-theme-text-primary">{{ app.name }}</h1>
                    <a v-if="app.current_url" :href="app.current_url" target="_blank" rel="noopener"
                       class="text-theme-accent hover:underline text-sm mt-1 inline-block">
                        {{ app.current_url }} ↗
                    </a>
                </div>
                <button v-if="canEdit" @click="editing = true"
                    class="text-xs px-3 py-1.5 rounded border border-theme-border text-theme-text-muted hover:text-theme-accent hover:border-theme-accent transition shrink-0">
                    Edit App
                </button>
            </div>
            <p v-if="app.description" class="mt-3 text-theme-text-secondary">{{ app.description }}</p>

            <!-- Team -->
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <span class="text-xs text-theme-text-muted font-semibold uppercase tracking-wide">Team:</span>
                <Link
                    v-for="v in app.victors"
                    :key="v.id"
                    :href="route('victory-games.victors.show', v.slug)"
                    class="flex items-center gap-2 text-sm text-theme-text-secondary hover:text-theme-accent transition"
                >
                    <img v-if="v.avatar_url" :src="v.avatar_url" :alt="v.display_name" class="w-6 h-6 rounded-full object-cover" />
                    <span v-else class="w-6 h-6 rounded-full bg-theme-elevated flex items-center justify-center text-xs font-bold text-theme-text-muted">
                        {{ v.display_name.charAt(0) }}
                    </span>
                    <span>{{ v.display_name }}</span>
                    <span v-if="v.role === 'owner'" class="text-xs text-theme-text-faint">(owner)</span>
                    <button v-if="canEdit" @click.prevent="removeMember(v.slug)"
                        class="text-theme-text-faint hover:text-theme-danger text-xs ml-1 transition">✕</button>
                </Link>
                <button v-if="canEdit" @click="addingMember = !addingMember"
                    class="text-xs text-theme-accent hover:underline">+ Add member</button>
            </div>

            <!-- Add member inline form -->
            <div v-if="addingMember" class="mt-3 flex gap-2 items-center">
                <input v-model="memberForm.victor_slug" type="text" placeholder="Victor slug"
                    class="rounded-lg border border-theme-border bg-theme-input text-theme-text-primary px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-theme-accent-ring w-48" />
                <button @click="submitAddMember" :disabled="memberForm.processing"
                    class="px-3 py-1.5 rounded-lg bg-theme-btn-primary text-theme-btn-primary-text text-sm hover:bg-theme-btn-primary-hover transition disabled:opacity-50">
                    Add
                </button>
                <button @click="addingMember = false" class="text-sm text-theme-text-muted hover:text-theme-text-primary transition">Cancel</button>
                <p v-if="memberForm.errors.victor_slug" class="text-xs text-danger">{{ memberForm.errors.victor_slug }}</p>
            </div>
        </div>

        <!-- Edit form -->
        <div v-else class="mb-8 bg-theme-card border border-theme-border rounded-xl p-6">
            <h2 class="text-lg font-bold text-theme-text-primary mb-4">Edit App</h2>
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-theme-text-secondary mb-1">Name</label>
                    <input v-model="editForm.name" type="text" required
                        class="w-full rounded-lg border border-theme-border bg-theme-input text-theme-text-primary px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-theme-accent-ring" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-theme-text-secondary mb-1">Description</label>
                    <textarea v-model="editForm.description" rows="3"
                        class="w-full rounded-lg border border-theme-border bg-theme-input text-theme-text-primary px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-theme-accent-ring" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-theme-text-secondary mb-1">Current URL</label>
                    <input v-model="editForm.current_url" type="url"
                        class="w-full rounded-lg border border-theme-border bg-theme-input text-theme-text-primary px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-theme-accent-ring" />
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="editForm.processing"
                        class="px-4 py-2 rounded-lg bg-theme-btn-primary text-theme-btn-primary-text text-sm font-semibold hover:bg-theme-btn-primary-hover transition disabled:opacity-50">
                        Save
                    </button>
                    <button type="button" @click="editing = false"
                        class="px-4 py-2 rounded-lg border border-theme-border text-theme-text-secondary text-sm hover:text-theme-text-primary transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <div ref="runComposer" class="mb-8 rounded-xl border border-theme-border bg-theme-card p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-theme-text-primary">Native AIUX Testing</h2>
                    <p class="mt-1 text-sm text-theme-text-secondary">
                        Queue a Playwright PHP browser run driven by the Laravel AI SDK. Workers execute the test in the background and stream progress to the run page.
                    </p>
                </div>
                <button
                    v-if="nativeRun.canStart"
                    type="button"
                    @click="showingRunForm = !showingRunForm"
                    class="rounded-lg border border-theme-border px-3 py-1.5 text-xs font-medium text-theme-text-muted transition hover:border-theme-accent hover:text-theme-accent"
                >
                    {{ showingRunForm ? 'Hide form' : 'New native run' }}
                </button>
            </div>

            <form v-if="nativeRun.canStart && showingRunForm" @submit.prevent="submitRun" class="mt-5 space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-theme-text-secondary">Goal</label>
                    <textarea
                        v-model="runForm.goal"
                        rows="3"
                        required
                        placeholder="Describe what the agent should try to achieve."
                        class="w-full rounded-lg border border-theme-border bg-theme-input px-3 py-2 text-sm text-theme-text-primary focus:outline-none focus:ring-2 focus:ring-theme-accent-ring"
                    />
                    <p v-if="runForm.errors.goal" class="mt-1 text-xs text-danger">{{ runForm.errors.goal }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-theme-text-secondary">Start URL</label>
                    <input
                        v-model="runForm.start_url"
                        type="url"
                        placeholder="https://example.com"
                        class="w-full rounded-lg border border-theme-border bg-theme-input px-3 py-2 text-sm text-theme-text-primary focus:outline-none focus:ring-2 focus:ring-theme-accent-ring"
                    />
                    <p class="mt-1 text-xs text-theme-text-muted">Leave this as the app URL if the test should begin on the current homepage.</p>
                    <p v-if="runForm.errors.start_url" class="mt-1 text-xs text-danger">{{ runForm.errors.start_url }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-theme-text-secondary">Mode</label>
                        <select
                            v-model="runForm.mode"
                            class="w-full rounded-lg border border-theme-border bg-theme-input px-3 py-2 text-sm text-theme-text-primary focus:outline-none focus:ring-2 focus:ring-theme-accent-ring"
                        >
                            <option value="desktop">Desktop</option>
                            <option value="mobile">Mobile</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-theme-text-secondary">Provider</label>
                        <select
                            v-model="runForm.provider"
                            class="w-full rounded-lg border border-theme-border bg-theme-input px-3 py-2 text-sm text-theme-text-primary focus:outline-none focus:ring-2 focus:ring-theme-accent-ring"
                        >
                            <option v-for="provider in providerOptions" :key="provider.key" :value="provider.key">
                                {{ provider.label }}
                            </option>
                        </select>
                        <p v-if="runForm.errors.provider" class="mt-1 text-xs text-danger">{{ runForm.errors.provider }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-theme-text-secondary">Model</label>
                        <input
                            v-model="runForm.model"
                            type="text"
                            required
                            class="w-full rounded-lg border border-theme-border bg-theme-input px-3 py-2 text-sm text-theme-text-primary focus:outline-none focus:ring-2 focus:ring-theme-accent-ring"
                        />
                        <p v-if="runForm.errors.model" class="mt-1 text-xs text-danger">{{ runForm.errors.model }}</p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-theme-text-secondary">Max steps</label>
                        <input
                            v-model.number="runForm.max_steps"
                            type="number"
                            :min="nativeRun.limits?.min_steps ?? 1"
                            :max="nativeRun.limits?.max_steps ?? 50"
                            class="w-full rounded-lg border border-theme-border bg-theme-input px-3 py-2 text-sm text-theme-text-primary focus:outline-none focus:ring-2 focus:ring-theme-accent-ring"
                        />
                        <p class="mt-1 text-xs text-theme-text-muted">
                            Choose how many planning/execution steps the agent is allowed to take before it stops.
                        </p>
                        <p v-if="runForm.errors.max_steps" class="mt-1 text-xs text-danger">{{ runForm.errors.max_steps }}</p>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button
                        type="submit"
                        :disabled="runForm.processing"
                        class="rounded-lg bg-theme-btn-primary px-4 py-2 text-sm font-semibold text-theme-btn-primary-text transition hover:bg-theme-btn-primary-hover disabled:opacity-50"
                    >
                        Queue Native Run
                    </button>
                    <button
                        type="button"
                        @click="showingRunForm = false"
                        class="rounded-lg border border-theme-border px-4 py-2 text-sm text-theme-text-secondary transition hover:text-theme-text-primary"
                    >
                        Cancel
                    </button>
                </div>
            </form>

            <div v-else-if="!nativeRun.providers.length" class="mt-5 rounded-lg bg-theme-elevated px-4 py-3 text-sm text-theme-text-muted">
                Native runs are unavailable because no AI providers are configured for this environment.
            </div>

            <div v-else-if="$page.props.auth?.user" class="mt-5 rounded-lg bg-theme-elevated px-4 py-3 text-sm text-theme-text-muted">
                Native runs are available to this app’s team members. Create your own victor profile and app if you want to run tests independently.
            </div>

            <div v-else class="mt-5 rounded-lg bg-theme-elevated px-4 py-3 text-sm text-theme-text-muted">
                Log in to create a victor profile, make an app, and queue native AIUX runs.
            </div>
        </div>

        <!-- Run history -->
        <div>
            <h2 class="text-xl font-bold text-theme-text-primary mb-4">Run History</h2>

            <div v-if="entries.length" class="space-y-4">
                <div v-for="entry in entries" :key="entry.id"
                    class="bg-theme-card border border-theme-border rounded-xl p-5">
                    <div class="flex items-start gap-4">
                        <!-- Placement badge (competition runs only) -->
                        <div class="shrink-0 w-10 text-center">
                            <span v-if="entry.placement" class="text-xl">{{ placementEmoji[entry.placement] }}</span>
                            <span v-else-if="entry.competition" class="text-xs text-theme-text-faint font-semibold uppercase block mt-1">Part.</span>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-baseline gap-2 flex-wrap">
                                <!-- Competition badge -->
                                <Link v-if="entry.competition"
                                    :href="route('victory-games.competitions.show', entry.competition.slug)"
                                    class="text-xs bg-theme-elevated px-2 py-0.5 rounded text-theme-text-muted hover:text-theme-accent transition">
                                    {{ entry.competition.name }}
                                </Link>
                                <span v-else class="text-xs bg-theme-elevated px-2 py-0.5 rounded text-theme-text-muted">Standalone run</span>
                                <span class="text-xs text-theme-text-faint">{{ formatDate(entry.submitted_at) }}</span>
                            </div>

                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                <span class="rounded-full bg-theme-elevated px-2 py-0.5 text-theme-text-muted">
                                    {{ entry.run_origin === 'native' ? 'Native AIUX' : 'AIUXTester import' }}
                                </span>
                                <span
                                    v-if="entry.session_status"
                                    class="rounded-full px-2 py-0.5 capitalize"
                                    :class="statusBadgeClass(entry.session_status)"
                                >
                                    {{ formatStatus(entry.session_status) }}
                                </span>
                            </div>

                            <div class="mt-1 text-sm text-theme-text-secondary truncate" :title="entry.app_url">
                                {{ entry.app_hostname }}
                            </div>

                            <p v-if="entry.entry_profile?.what_it_does" class="mt-2 text-sm text-theme-text-muted line-clamp-2">
                                {{ entry.entry_profile.what_it_does }}
                            </p>

                            <div class="mt-3 flex items-center gap-4 text-xs text-theme-text-muted">
                                <span>{{ entry.step_count }} steps</span>
                                <Link :href="route('victory-games.runs.show', entry.id)" class="text-theme-accent hover:underline">
                                    View run →
                                </Link>
                                <button
                                    v-if="nativeRun.canStart"
                                    type="button"
                                    @click="reuseRunConfig(entry)"
                                    class="text-theme-text-muted transition hover:text-theme-accent"
                                >
                                    Reuse config
                                </button>
                                <button
                                    v-if="entry.can_delete"
                                    type="button"
                                    @click="deleteRun(entry)"
                                    class="text-theme-danger transition hover:text-theme-danger/80"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-12 text-theme-text-muted">
                {{ nativeRun.canStart ? 'No runs yet. Queue a native run above or import one from AIUXTester.' : 'No runs yet. Export a session from AIUXTester to get started.' }}
            </div>
        </div>

    </div>
</template>
