<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    entries: Array,
    victors: Array,
    apps:    Array,
    filter:  String,
});

const page  = usePage();
const flash = computed(() => page.props.flash ?? {});

const editingId  = ref(null);
const editForm   = useForm({ victor_id: null, app_id: null });

function startEdit(entry) {
    editingId.value   = entry.id;
    editForm.victor_id = entry.victor_id ?? '';
    editForm.app_id    = entry.app_id ?? '';
}

function cancelEdit() {
    editingId.value = null;
    editForm.reset();
}

function saveEdit(entry) {
    editForm.patch(route('admin.victory-games.runs.update', entry.id), {
        preserveScroll: true,
        onSuccess: () => { editingId.value = null; },
    });
}

function setFilter(f) {
    router.get(route('admin.victory-games.runs.index'), { filter: f }, { preserveState: false });
}

function deleteRun(entry) {
    if (!confirm(`Delete the run for "${entry.app_hostname}"? This cannot be undone.`)) return;
    router.delete(route('victory-games.runs.destroy', entry.id), {}, { preserveScroll: true });
}

function formatDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
</script>

<template>
    <Head title="Admin — Victory Games Runs" />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <!-- Header -->
        <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
            <div>
                <h1 class="text-2xl font-bold text-theme-text-primary">All Runs</h1>
                <p class="text-sm text-theme-text-muted mt-1">Assign or reassign any run to a victor and/or app.</p>
            </div>
            <Link :href="route('admin.victory-games.index')" class="text-sm text-theme-text-muted hover:text-theme-accent transition">
                &larr; Victory Games Admin
            </Link>
        </div>

        <!-- Flash -->
        <div v-if="flash.success" class="mb-5 rounded-lg bg-theme-success/10 border border-theme-success/30 px-4 py-3 text-sm text-theme-success">
            {{ flash.success }}
        </div>

        <!-- Filters -->
        <div class="mb-5 flex gap-2">
            <button v-for="[key, label] in [['all','All'], ['no_victor','No Victor'], ['no_app','No App']]"
                :key="key"
                @click="setFilter(key)"
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition"
                :class="filter === key
                    ? 'bg-theme-accent text-white'
                    : 'bg-theme-elevated text-theme-text-secondary hover:text-theme-text-primary'">
                {{ label }}
            </button>
            <span class="ml-auto text-sm text-theme-text-muted self-center">{{ entries.length }} run{{ entries.length !== 1 ? 's' : '' }}</span>
        </div>

        <!-- Table -->
        <div v-if="entries.length" class="bg-theme-card border border-theme-border rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-theme-border bg-theme-elevated text-left text-xs text-theme-text-muted uppercase tracking-wide">
                        <th class="px-4 py-3">App / Goal</th>
                        <th class="px-4 py-3">Competition</th>
                        <th class="px-4 py-3">Victor</th>
                        <th class="px-4 py-3">App</th>
                        <th class="px-4 py-3">Steps</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-theme-border">
                    <tr v-for="entry in entries" :key="entry.id"
                        :class="editingId === entry.id ? 'bg-theme-elevated' : 'hover:bg-theme-elevated/50 transition'">

                        <!-- App / Goal -->
                        <td class="px-4 py-3 max-w-[180px]">
                            <div class="font-medium text-theme-text-primary truncate" :title="entry.app_url">
                                {{ entry.app_hostname || '—' }}
                            </div>
                            <div v-if="entry.app_goal" class="text-xs text-theme-text-muted truncate mt-0.5" :title="entry.app_goal">
                                {{ entry.app_goal }}
                            </div>
                        </td>

                        <!-- Competition -->
                        <td class="px-4 py-3">
                            <Link v-if="entry.competition"
                                :href="route('victory-games.competitions.show', entry.competition.slug)"
                                class="text-theme-accent hover:underline text-xs">
                                {{ entry.competition.name }}
                            </Link>
                            <span v-else class="text-xs text-theme-text-faint">standalone</span>
                        </td>

                        <!-- Victor (view or edit) -->
                        <td class="px-4 py-3">
                            <template v-if="editingId === entry.id">
                                <select v-model="editForm.victor_id"
                                    class="w-full rounded border border-theme-border bg-theme-input text-theme-text-primary px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-theme-accent-ring">
                                    <option value="">— unassigned —</option>
                                    <option v-for="v in victors" :key="v.id" :value="v.id">{{ v.display_name }}</option>
                                </select>
                            </template>
                            <template v-else>
                                <Link v-if="entry.victor"
                                    :href="route('victory-games.victors.show', entry.victor.slug)"
                                    class="text-theme-accent hover:underline text-xs">
                                    {{ entry.victor.display_name }}
                                </Link>
                                <span v-else class="text-xs text-theme-danger font-medium">Unassigned</span>
                            </template>
                        </td>

                        <!-- App (view or edit) -->
                        <td class="px-4 py-3">
                            <template v-if="editingId === entry.id">
                                <select v-model="editForm.app_id"
                                    class="w-full rounded border border-theme-border bg-theme-input text-theme-text-primary px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-theme-accent-ring">
                                    <option value="">— none —</option>
                                    <option v-for="a in apps" :key="a.id" :value="a.id">{{ a.name }}</option>
                                </select>
                            </template>
                            <template v-else>
                                <Link v-if="entry.app"
                                    :href="route('victory-games.apps.show', entry.app.slug)"
                                    class="text-theme-accent hover:underline text-xs">
                                    {{ entry.app.name }}
                                </Link>
                                <span v-else class="text-xs text-theme-text-faint">—</span>
                            </template>
                        </td>

                        <!-- Steps -->
                        <td class="px-4 py-3 text-theme-text-muted text-xs">{{ entry.step_count }}</td>

                        <!-- Date -->
                        <td class="px-4 py-3 text-theme-text-muted text-xs whitespace-nowrap">{{ formatDate(entry.submitted_at) }}</td>

                        <!-- Actions -->
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <template v-if="editingId === entry.id">
                                <button @click="saveEdit(entry)" :disabled="editForm.processing"
                                    class="text-xs px-2 py-1 rounded bg-theme-btn-primary text-theme-btn-primary-text hover:bg-theme-btn-primary-hover transition disabled:opacity-50 mr-1">
                                    Save
                                </button>
                                <button @click="cancelEdit"
                                    class="text-xs px-2 py-1 rounded border border-theme-border text-theme-text-muted hover:text-theme-text-primary transition">
                                    Cancel
                                </button>
                            </template>
                            <template v-else>
                                <Link :href="route('victory-games.runs.show', entry.id)"
                                    class="text-xs text-theme-text-muted hover:text-theme-accent transition mr-3">
                                    View
                                </Link>
                                <button @click="startEdit(entry)"
                                    class="text-xs px-2 py-1 rounded border border-theme-border text-theme-text-muted hover:text-theme-accent hover:border-theme-accent transition mr-2">
                                    Reassign
                                </button>
                                <button @click="deleteRun(entry)"
                                    class="text-xs px-2 py-1 rounded border border-theme-danger/40 text-theme-danger hover:bg-theme-danger/10 transition">
                                    Delete
                                </button>
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="text-center py-16 bg-theme-card border border-theme-border rounded-xl text-theme-text-muted">
            No runs match this filter.
        </div>
    </div>
</template>
