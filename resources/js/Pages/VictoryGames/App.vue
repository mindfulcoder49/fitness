<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import MagazineLayout from '@/Layouts/MagazineLayout.vue';

defineOptions({ layout: MagazineLayout });

const props = defineProps({
    app:     Object,
    entries: Array,
    canEdit: Boolean,
});

const editing       = ref(false);
const addingMember  = ref(false);

const editForm = useForm({
    _method:     'patch',
    name:        props.app.name,
    description: props.app.description ?? '',
    current_url: props.app.current_url ?? '',
});

const memberForm = useForm({ victor_slug: '' });

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

const placementEmoji = { 1: '🥇', 2: '🥈', 3: '🥉' };

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
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

                            <div class="mt-1 text-sm text-theme-text-secondary truncate" :title="entry.app_url">
                                {{ entry.app_hostname }}
                            </div>

                            <p v-if="entry.entry_profile?.what_it_does" class="mt-2 text-sm text-theme-text-muted line-clamp-2">
                                {{ entry.entry_profile.what_it_does }}
                            </p>

                            <div class="mt-3 flex items-center gap-4 text-xs text-theme-text-muted">
                                <span>{{ entry.step_count }} steps</span>
                                <Link :href="route('victory-games.runs.show', entry.id)"
                                    class="text-theme-accent hover:underline">
                                    View run →
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-12 text-theme-text-muted">
                No runs yet. Export a session from AIUXTester to get started.
            </div>
        </div>

    </div>
</template>
