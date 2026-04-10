<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import MagazineLayout from '@/Layouts/MagazineLayout.vue';

defineOptions({ layout: MagazineLayout });

const props = defineProps({
    victor:  Object,
    entries: Array,
    canEdit: Boolean,
});

const editing     = ref(false);
const claimOpen   = ref(false);

const form = useForm({
    _method:      'patch',
    display_name: props.victor.display_name,
    email:        props.victor.email ?? '',
    bio:          props.victor.bio ?? '',
    github_url:   props.victor.github_url ?? '',
    website_url:  props.victor.website_url ?? '',
    twitter_url:  props.victor.twitter_url ?? '',
    avatar:       null,
});

const claimForm = useForm({ external_user_id: '' });

function submitEdit() {
    form.post(route('victory-games.victors.update', props.victor.slug), {
        forceFormData: true,
        onSuccess: () => { editing.value = false; },
    });
}

function submitClaim() {
    claimForm.post(route('victory-games.victors.claim', props.victor.slug), {
        onSuccess: () => { claimOpen.value = false; },
    });
}

const placementEmoji = { 1: '🥇', 2: '🥈', 3: '🥉' };

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
}
</script>

<template>
    <Head :title="`${victor.display_name} — VibeCode Victory Games`" />

    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-10">

        <!-- Breadcrumb -->
        <nav class="text-sm text-theme-text-muted mb-6 flex items-center gap-2">
            <Link :href="route('victory-games.home')" class="hover:text-theme-accent transition">VibeCode Victory Games</Link>
            <span>/</span>
            <Link :href="route('victory-games.victors')" class="hover:text-theme-accent transition">Victors</Link>
            <span>/</span>
            <span class="text-theme-text-primary">{{ victor.display_name }}</span>
        </nav>

        <!-- Profile header -->
        <div v-if="!editing" class="flex items-start gap-6 mb-10">
            <img
                v-if="victor.avatar_url"
                :src="victor.avatar_url"
                :alt="victor.display_name"
                class="w-20 h-20 rounded-full object-cover shrink-0"
            />
            <div v-else class="w-20 h-20 rounded-full bg-theme-elevated flex items-center justify-center text-2xl font-bold text-theme-text-muted shrink-0">
                {{ victor.display_name.charAt(0).toUpperCase() }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-extrabold text-theme-text-primary">{{ victor.display_name }}</h1>
                    <button v-if="canEdit" @click="editing = true" class="text-xs px-3 py-1 rounded border border-theme-border text-theme-text-muted hover:text-theme-accent hover:border-theme-accent transition">
                        Edit Profile
                    </button>
                    <button v-else-if="!canEdit && $page.props.auth?.user" @click="claimOpen = true" class="text-xs px-3 py-1 rounded border border-theme-border text-theme-text-muted hover:text-theme-accent hover:border-theme-accent transition">
                        Claim Profile
                    </button>
                </div>
                <p v-if="victor.bio" class="mt-2 text-theme-text-secondary">{{ victor.bio }}</p>
                <div class="mt-3 flex flex-wrap gap-3 text-sm">
                    <a v-if="victor.github_url" :href="victor.github_url" target="_blank" rel="noopener" class="text-theme-accent hover:underline">GitHub</a>
                    <a v-if="victor.website_url" :href="victor.website_url" target="_blank" rel="noopener" class="text-theme-accent hover:underline">Website</a>
                    <a v-if="victor.twitter_url" :href="victor.twitter_url" target="_blank" rel="noopener" class="text-theme-accent hover:underline">Twitter/X</a>
                </div>
            </div>
        </div>

        <!-- Edit form -->
        <div v-else class="mb-10 bg-theme-card border border-theme-border rounded-xl p-6">
            <h2 class="text-lg font-bold text-theme-text-primary mb-4">Edit Profile</h2>
            <form @submit.prevent="submitEdit" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-theme-text-secondary mb-1">Display Name</label>
                    <input v-model="form.display_name" type="text" required class="w-full rounded-lg border border-theme-border bg-theme-input text-theme-text-primary px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-theme-accent-ring" />
                    <p v-if="form.errors.display_name" class="text-xs text-danger mt-1">{{ form.errors.display_name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-theme-text-secondary mb-1">Email</label>
                    <input v-model="form.email" type="email" class="w-full rounded-lg border border-theme-border bg-theme-input text-theme-text-primary px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-theme-accent-ring" />
                    <p class="text-xs text-theme-text-muted mt-1">Used for competition notifications. Not displayed publicly.</p>
                    <p v-if="form.errors.email" class="text-xs text-danger mt-1">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-theme-text-secondary mb-1">Bio</label>
                    <textarea v-model="form.bio" rows="3" class="w-full rounded-lg border border-theme-border bg-theme-input text-theme-text-primary px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-theme-accent-ring" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-theme-text-secondary mb-1">GitHub URL</label>
                        <input v-model="form.github_url" type="url" class="w-full rounded-lg border border-theme-border bg-theme-input text-theme-text-primary px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-theme-accent-ring" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-theme-text-secondary mb-1">Website</label>
                        <input v-model="form.website_url" type="url" class="w-full rounded-lg border border-theme-border bg-theme-input text-theme-text-primary px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-theme-accent-ring" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-theme-text-secondary mb-1">Twitter/X URL</label>
                        <input v-model="form.twitter_url" type="url" class="w-full rounded-lg border border-theme-border bg-theme-input text-theme-text-primary px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-theme-accent-ring" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-theme-text-secondary mb-1">Avatar (optional)</label>
                    <input type="file" accept="image/*" @change="form.avatar = $event.target.files[0]" class="text-sm text-theme-text-secondary" />
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 rounded-lg bg-theme-btn-primary text-theme-btn-primary-text text-sm font-semibold hover:bg-theme-btn-primary-hover transition disabled:opacity-50">
                        Save
                    </button>
                    <button type="button" @click="editing = false" class="px-4 py-2 rounded-lg border border-theme-border text-theme-text-secondary text-sm hover:text-theme-text-primary transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- Claim profile modal -->
        <div v-if="claimOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-theme-card border border-theme-border rounded-xl p-6 w-full max-w-sm mx-4">
                <h2 class="text-lg font-bold text-theme-text-primary mb-2">Claim This Profile</h2>
                <p class="text-sm text-theme-text-secondary mb-4">
                    Enter your AIUXTester user ID to claim this profile and unlock editing.
                </p>
                <form @submit.prevent="submitClaim" class="space-y-3">
                    <input v-model="claimForm.external_user_id" type="text" placeholder="AIUXTester user ID" required
                        class="w-full rounded-lg border border-theme-border bg-theme-input text-theme-text-primary px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-theme-accent-ring" />
                    <p v-if="claimForm.errors.external_user_id" class="text-xs text-danger">{{ claimForm.errors.external_user_id }}</p>
                    <div class="flex gap-2">
                        <button type="submit" :disabled="claimForm.processing" class="flex-1 px-4 py-2 rounded-lg bg-theme-btn-primary text-theme-btn-primary-text text-sm font-semibold hover:bg-theme-btn-primary-hover transition disabled:opacity-50">
                            Claim
                        </button>
                        <button type="button" @click="claimOpen = false" class="px-4 py-2 rounded-lg border border-theme-border text-theme-text-secondary text-sm hover:text-theme-text-primary transition">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Competition entries -->
        <div>
            <h2 class="text-xl font-bold text-theme-text-primary mb-4">Competition History</h2>

            <div v-if="entries.length" class="space-y-4">
                <div
                    v-for="entry in entries"
                    :key="entry.id"
                    class="bg-theme-card border border-theme-border rounded-xl p-5 flex flex-col sm:flex-row sm:items-start gap-4"
                >
                    <!-- Placement -->
                    <div class="shrink-0 w-12 text-center">
                        <span v-if="entry.placement" class="text-2xl">{{ placementEmoji[entry.placement] }}</span>
                        <span v-else class="text-xs text-theme-text-faint font-semibold uppercase block mt-1">Part.</span>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline gap-2 flex-wrap">
                            <Link
                                :href="route('victory-games.competitions.show', entry.competition.slug)"
                                class="font-bold text-theme-text-primary hover:text-theme-accent transition"
                            >
                                {{ entry.competition.name }}
                            </Link>
                            <span class="text-xs text-theme-text-muted">{{ formatDate(entry.competition.held_at) }}</span>
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
                                View full run →
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-12 text-theme-text-muted">
                No competition entries yet.
            </div>
        </div>
    </div>
</template>
