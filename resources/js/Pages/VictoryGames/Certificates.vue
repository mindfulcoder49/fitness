<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

defineProps({
    has_profile:  Boolean,
    certificates: Array,
});

const typeEmoji = {
    '1st':           '🥇',
    '2nd':           '🥈',
    '3rd':           '🥉',
    'participation': '🏅',
};

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric',
    });
}
</script>

<template>
    <Head title="My Certificates — VibeCode Victory Games" />

    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-10">

        <nav class="text-sm text-theme-text-muted mb-6 flex items-center gap-2">
            <Link :href="route('victory-games.home')" class="hover:text-theme-accent transition">VibeCode Victory Games</Link>
            <span>/</span>
            <span class="text-theme-text-primary">My Certificates</span>
        </nav>

        <h1 class="text-3xl font-extrabold text-theme-text-primary mb-2">My Certificates</h1>
        <p class="text-theme-text-secondary mb-8">
            Download your certificates of participation and achievement from the VibeCode Victory Games.
        </p>

        <!-- No victor profile linked -->
        <div v-if="!has_profile" class="text-center py-16 bg-theme-card border border-theme-border rounded-xl">
            <div class="text-4xl mb-4">🏅</div>
            <p class="text-theme-text-secondary mb-4">
                Your account isn't linked to a victor profile yet.
            </p>
            <Link :href="route('victory-games.victors')" class="text-theme-accent hover:underline text-sm">
                Browse victors and claim your profile →
            </Link>
        </div>

        <!-- Certificates list -->
        <div v-else-if="certificates.length" class="space-y-4">
            <div
                v-for="cert in certificates"
                :key="cert.id"
                class="bg-theme-card border border-theme-border rounded-xl p-5 flex items-center gap-5"
            >
                <div class="text-3xl shrink-0">{{ typeEmoji[cert.certificate_type] ?? '🏅' }}</div>
                <div class="flex-1 min-w-0">
                    <div class="font-bold text-theme-text-primary">{{ cert.type_label }}</div>
                    <div class="text-sm text-theme-text-secondary truncate">
                        <Link :href="route('victory-games.competitions.show', cert.competition.slug)" class="hover:text-theme-accent transition">
                            {{ cert.competition.name }}
                        </Link>
                    </div>
                    <div class="text-xs text-theme-text-muted mt-0.5">
                        <Link :href="route('victory-games.victors.show', cert.victor.slug)" class="hover:text-theme-accent transition">
                            {{ cert.victor.display_name }}
                        </Link>
                        &middot; Issued {{ formatDate(cert.issued_at) }}
                    </div>
                </div>
                <a
                    :href="route('victory-games.certificates.download', cert.download_token)"
                    class="shrink-0 px-4 py-2 rounded-lg bg-theme-btn-primary text-theme-btn-primary-text text-sm font-semibold hover:bg-theme-btn-primary-hover transition"
                >
                    Download PDF
                </a>
            </div>
        </div>

        <div v-else class="text-center py-16 bg-theme-card border border-theme-border rounded-xl">
            <div class="text-4xl mb-4">🏗️</div>
            <p class="text-theme-text-secondary">No certificates yet. Compete in a VibeCode Victory Games event to earn one!</p>
        </div>
    </div>
</template>
