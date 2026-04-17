<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref, watch } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    competitions: Array,
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});
const openRecipientPanels = ref({});
const selectedVictorIds = ref({});

watch(() => props.competitions, (competitions) => {
    selectedVictorIds.value = Object.fromEntries(
        competitions.map((comp) => [comp.id, [...(comp.default_victor_ids || [])]])
    );
}, { immediate: true });

function formatDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function toggleRecipients(comp) {
    openRecipientPanels.value = {
        ...openRecipientPanels.value,
        [comp.id]: !openRecipientPanels.value[comp.id],
    };
}

function selectedIds(comp) {
    return selectedVictorIds.value[comp.id] || [];
}

function setSelection(comp, ids) {
    selectedVictorIds.value = {
        ...selectedVictorIds.value,
        [comp.id]: [...ids],
    };
}

function toggleVictor(comp, victorId) {
    const ids = [...selectedIds(comp)];
    const index = ids.indexOf(victorId);

    if (index === -1) {
        ids.push(victorId);
    } else {
        ids.splice(index, 1);
    }

    setSelection(comp, ids);
}

function selectAll(comp) {
    setSelection(comp, comp.victors.filter(v => v.has_email).map(v => v.id));
}

function selectUnsent(comp) {
    setSelection(comp, comp.victors.filter(v => v.has_email && !v.welcome_email_sent_at).map(v => v.id));
}

function clearSelection(comp) {
    setSelection(comp, []);
}

function sendEmails(comp) {
    const ids = selectedIds(comp);
    if (!ids.length) return;
    if (!confirm(`Send welcome emails to ${ids.length} selected victor(s) in "${comp.name}"?`)) return;

    router.post(route('admin.victory-games.competitions.send-welcome-emails', comp.id), {
        victor_ids: ids,
    }, {
        preserveScroll: true,
    });
}

function deleteCompetition(comp) {
    if (!confirm(`Delete "${comp.name}" and ALL its runs? This cannot be undone.`)) return;
    router.delete(route('admin.victory-games.competitions.destroy', comp.id));
}
</script>

<template>
    <Head title="Admin — Victory Games" />

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-theme-text-primary">VibeCode Victory Games</h1>
                <p class="text-sm text-theme-text-muted mt-1">Manage competitions and send welcome emails to victors.</p>
            </div>
            <div class="flex items-center gap-4">
                <Link :href="route('admin.victory-games.runs.index')" class="text-sm text-theme-accent hover:underline transition">
                    Manage Runs →
                </Link>
                <Link :href="route('admin.dashboard')" class="text-sm text-theme-text-muted hover:text-theme-accent transition">
                    &larr; Admin Dashboard
                </Link>
            </div>
        </div>

        <!-- Flash messages -->
        <div v-if="flash.success" class="mb-6 rounded-lg bg-theme-success/10 border border-theme-success/30 px-4 py-3 text-sm text-theme-success">
            {{ flash.success }}
        </div>
        <div v-if="flash.error" class="mb-6 rounded-lg bg-theme-danger/10 border border-theme-danger/30 px-4 py-3 text-sm text-theme-danger">
            {{ flash.error }}
        </div>

        <!-- Import section -->
        <div class="mb-8 bg-theme-card border border-theme-border rounded-xl p-5">
            <h2 class="text-base font-semibold text-theme-text-primary mb-1">Import Competition</h2>
            <p class="text-sm text-theme-text-muted mb-3">
                POST a competition export JSON to
                <code class="bg-theme-elevated px-1 rounded text-xs">{{ route('admin.victory-games.import') }}</code>
                with <code class="bg-theme-elevated px-1 rounded text-xs">Authorization: Bearer &lt;token&gt;</code>.
            </p>
        </div>

        <!-- Competitions list -->
        <div v-if="competitions.length" class="space-y-4">
            <div
                v-for="comp in competitions"
                :key="comp.id"
                class="bg-theme-card border border-theme-border rounded-xl p-5"
            >
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <div class="font-semibold text-theme-text-primary text-base">{{ comp.name }}</div>
                        <div class="text-sm text-theme-text-muted mt-0.5">{{ formatDate(comp.held_at) }}</div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button
                            @click="toggleRecipients(comp)"
                            class="px-4 py-2 rounded-lg bg-theme-btn-primary text-theme-btn-primary-text text-sm font-semibold hover:bg-theme-btn-primary-hover transition disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            Choose Recipients
                        </button>
                        <button
                            @click="deleteCompetition(comp)"
                            class="px-3 py-2 rounded-lg border border-theme-danger/40 text-theme-danger text-sm font-medium hover:bg-theme-danger/10 transition"
                        >
                            Delete
                        </button>
                    </div>
                </div>

                <!-- Stats row -->
                <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="bg-theme-elevated rounded-lg px-3 py-2 text-center">
                        <div class="text-lg font-bold text-theme-text-primary">{{ comp.victor_count }}</div>
                        <div class="text-xs text-theme-text-muted">Victors</div>
                    </div>
                    <div class="bg-theme-elevated rounded-lg px-3 py-2 text-center">
                        <div class="text-lg font-bold" :class="comp.with_email > 0 ? 'text-theme-text-primary' : 'text-theme-text-faint'">{{ comp.with_email }}</div>
                        <div class="text-xs text-theme-text-muted">With Email</div>
                    </div>
                    <div class="bg-theme-elevated rounded-lg px-3 py-2 text-center">
                        <div class="text-lg font-bold text-theme-text-primary">{{ comp.claimed }}</div>
                        <div class="text-xs text-theme-text-muted">Claimed</div>
                    </div>
                    <div class="bg-theme-elevated rounded-lg px-3 py-2 text-center">
                        <div class="text-lg font-bold" :class="comp.emails_sent > 0 ? 'text-theme-success' : 'text-theme-text-faint'">{{ comp.emails_sent }}</div>
                        <div class="text-xs text-theme-text-muted">Emails Sent</div>
                    </div>
                </div>

                <div v-if="comp.with_email === 0" class="mt-3 text-xs text-theme-text-muted">
                    No victors have email addresses. Re-import with <code class="bg-theme-elevated px-1 rounded">user_email</code> in the export to enable emailing.
                </div>
                <div v-else-if="comp.emails_sent > 0 && comp.emails_sent < comp.with_email" class="mt-3 text-xs text-theme-text-muted">
                    {{ comp.emails_sent }} of {{ comp.with_email }} victors with email addresses have already been contacted.
                </div>
                <div v-else-if="comp.emails_sent >= comp.with_email && comp.with_email > 0" class="mt-3 text-xs text-theme-success">
                    All victors with email addresses have already been contacted. You can still resend to any selected subset.
                </div>

                <div v-if="openRecipientPanels[comp.id]" class="mt-4 border border-theme-border rounded-xl p-4 bg-theme-elevated/40">
                    <div class="flex items-center justify-between gap-3 flex-wrap mb-3">
                        <div>
                            <div class="text-sm font-medium text-theme-text-primary">
                                {{ selectedIds(comp).length }} recipient<span v-if="selectedIds(comp).length !== 1">s</span> selected
                            </div>
                            <div class="text-xs text-theme-text-muted">
                                Default selection includes victors with email addresses who have not been emailed yet.
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <button type="button" @click="selectUnsent(comp)" class="px-3 py-1.5 rounded-md bg-theme-card border border-theme-border text-theme-text-primary text-xs font-medium hover:border-theme-accent transition">
                                Unsent Only
                            </button>
                            <button type="button" @click="selectAll(comp)" class="px-3 py-1.5 rounded-md bg-theme-card border border-theme-border text-theme-text-primary text-xs font-medium hover:border-theme-accent transition">
                                Select All With Email
                            </button>
                            <button type="button" @click="clearSelection(comp)" class="px-3 py-1.5 rounded-md bg-theme-card border border-theme-border text-theme-text-primary text-xs font-medium hover:border-theme-accent transition">
                                Clear
                            </button>
                            <button
                                type="button"
                                @click="sendEmails(comp)"
                                :disabled="selectedIds(comp).length === 0"
                                class="px-4 py-2 rounded-lg bg-theme-btn-primary text-theme-btn-primary-text text-sm font-semibold hover:bg-theme-btn-primary-hover transition disabled:opacity-40 disabled:cursor-not-allowed"
                            >
                                Send To Selected
                            </button>
                        </div>
                    </div>

                    <div v-if="comp.victors.length" class="max-h-72 overflow-y-auto divide-y divide-theme-border rounded-lg border border-theme-border bg-theme-card">
                        <label
                            v-for="victor in comp.victors"
                            :key="victor.id"
                            class="flex items-start gap-3 px-4 py-3"
                            :class="victor.has_email ? 'cursor-pointer' : 'opacity-60 cursor-not-allowed'"
                        >
                            <input
                                type="checkbox"
                                class="mt-1 rounded border-theme-border text-theme-accent"
                                :checked="selectedIds(comp).includes(victor.id)"
                                :disabled="!victor.has_email"
                                @change="toggleVictor(comp, victor.id)"
                            />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-medium text-theme-text-primary">{{ victor.display_name }}</span>
                                    <span v-if="victor.claimed" class="text-[11px] px-2 py-0.5 rounded bg-theme-success/15 text-theme-success">Claimed</span>
                                    <span v-if="victor.welcome_email_sent_at" class="text-[11px] px-2 py-0.5 rounded bg-theme-warning/15 text-theme-warning">Emailed</span>
                                    <span v-if="!victor.has_email" class="text-[11px] px-2 py-0.5 rounded bg-theme-danger/15 text-theme-danger">No Email</span>
                                </div>
                                <div class="text-xs text-theme-text-muted mt-1">
                                    {{ victor.email || 'No email address on file' }}
                                </div>
                            </div>
                        </label>
                    </div>
                    <div v-else class="text-sm text-theme-text-muted">
                        No victors found for this competition.
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="text-center py-16 bg-theme-card border border-theme-border rounded-xl">
            <p class="text-theme-text-muted">No competitions imported yet.</p>
        </div>
    </div>
</template>
