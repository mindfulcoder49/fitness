<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    competitions: Array,
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

function formatDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function sendEmails(comp) {
    if (!confirm(`Send welcome emails to all ${comp.with_email} victor(s) with email addresses in "${comp.name}"?`)) return;
    router.post(route('admin.victory-games.competitions.send-welcome-emails', comp.id), {}, {
        preserveScroll: true,
    });
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
            <Link :href="route('admin.dashboard')" class="text-sm text-theme-text-muted hover:text-theme-accent transition">
                &larr; Admin Dashboard
            </Link>
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

                    <button
                        @click="sendEmails(comp)"
                        :disabled="comp.with_email === 0"
                        class="shrink-0 px-4 py-2 rounded-lg bg-theme-btn-primary text-theme-btn-primary-text text-sm font-semibold hover:bg-theme-btn-primary-hover transition disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        Send Welcome Emails
                    </button>
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
                    {{ comp.emails_sent }} of {{ comp.with_email }} victors have been emailed. Sending again will re-send to all.
                </div>
                <div v-else-if="comp.emails_sent >= comp.with_email && comp.with_email > 0" class="mt-3 text-xs text-theme-success">
                    All victors with emails have been contacted. Sending again will re-send to all.
                </div>
            </div>
        </div>

        <div v-else class="text-center py-16 bg-theme-card border border-theme-border rounded-xl">
            <p class="text-theme-text-muted">No competitions imported yet.</p>
        </div>
    </div>
</template>
