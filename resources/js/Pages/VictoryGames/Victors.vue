<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import MagazineLayout from '@/Layouts/MagazineLayout.vue';

defineOptions({ layout: MagazineLayout });

const props = defineProps({
    victors: Array,
    authVictor: Object,
});

const page = usePage();
const createProfileOpen = ref(false);
const createProfileForm = useForm({
    display_name: page.props.auth?.user?.name ?? '',
    bio: '',
    github_url: '',
    website_url: '',
    twitter_url: '',
});

const placementEmoji = { 1: '🥇', 2: '🥈', 3: '🥉' };

function submitCreateProfile() {
    createProfileForm.post(route('victory-games.victors.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createProfileOpen.value = false;
        },
    });
}
</script>

<template>
    <Head title="Our Victors — VibeCode Victory Games" />

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">

        <!-- Breadcrumb -->
        <nav class="text-sm text-theme-text-muted mb-6 flex items-center gap-2">
            <Link :href="route('victory-games.home')" class="hover:text-theme-accent transition">VibeCode Victory Games</Link>
            <span>/</span>
            <span class="text-theme-text-primary">Our Victors</span>
        </nav>

        <div class="mb-10">
            <h1 class="text-3xl font-extrabold text-theme-text-primary">Our Victors</h1>
            <p class="mt-2 text-theme-text-secondary">Builders who competed in the VibeCode Victory Games.</p>
        </div>

        <div
            v-if="$page.props.auth?.user && !props.authVictor"
            class="mb-8 rounded-2xl border border-theme-border bg-theme-card p-6"
        >
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-theme-text-primary">Create Your Victor Profile</h2>
                    <p class="mt-1 text-sm text-theme-text-secondary">
                        You need a victor profile before you can make an app and queue native AIUX tests.
                    </p>
                </div>
                <button
                    type="button"
                    @click="createProfileOpen = !createProfileOpen"
                    class="rounded-lg border border-theme-border px-3 py-1.5 text-xs font-medium text-theme-text-muted transition hover:border-theme-accent hover:text-theme-accent"
                >
                    {{ createProfileOpen ? 'Close' : 'Create profile' }}
                </button>
            </div>

            <form v-if="createProfileOpen" @submit.prevent="submitCreateProfile" class="mt-5 space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-theme-text-secondary">Display Name</label>
                    <input
                        v-model="createProfileForm.display_name"
                        type="text"
                        required
                        class="w-full rounded-lg border border-theme-border bg-theme-input px-3 py-2 text-sm text-theme-text-primary focus:outline-none focus:ring-2 focus:ring-theme-accent-ring"
                    />
                    <p v-if="createProfileForm.errors.display_name" class="mt-1 text-xs text-danger">{{ createProfileForm.errors.display_name }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-theme-text-secondary">Bio</label>
                    <textarea
                        v-model="createProfileForm.bio"
                        rows="3"
                        class="w-full rounded-lg border border-theme-border bg-theme-input px-3 py-2 text-sm text-theme-text-primary focus:outline-none focus:ring-2 focus:ring-theme-accent-ring"
                    />
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-theme-text-secondary">GitHub URL</label>
                        <input
                            v-model="createProfileForm.github_url"
                            type="url"
                            class="w-full rounded-lg border border-theme-border bg-theme-input px-3 py-2 text-sm text-theme-text-primary focus:outline-none focus:ring-2 focus:ring-theme-accent-ring"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-theme-text-secondary">Website URL</label>
                        <input
                            v-model="createProfileForm.website_url"
                            type="url"
                            class="w-full rounded-lg border border-theme-border bg-theme-input px-3 py-2 text-sm text-theme-text-primary focus:outline-none focus:ring-2 focus:ring-theme-accent-ring"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-theme-text-secondary">Twitter/X URL</label>
                        <input
                            v-model="createProfileForm.twitter_url"
                            type="url"
                            class="w-full rounded-lg border border-theme-border bg-theme-input px-3 py-2 text-sm text-theme-text-primary focus:outline-none focus:ring-2 focus:ring-theme-accent-ring"
                        />
                    </div>
                </div>

                <div class="flex gap-3">
                    <button
                        type="submit"
                        :disabled="createProfileForm.processing"
                        class="rounded-lg bg-theme-btn-primary px-4 py-2 text-sm font-semibold text-theme-btn-primary-text transition hover:bg-theme-btn-primary-hover disabled:opacity-50"
                    >
                        Create Victor Profile
                    </button>
                    <button
                        type="button"
                        @click="createProfileOpen = false"
                        class="rounded-lg border border-theme-border px-4 py-2 text-sm text-theme-text-secondary transition hover:text-theme-text-primary"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <div
            v-else-if="props.authVictor"
            class="mb-8 rounded-2xl border border-theme-border bg-theme-card p-6"
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-theme-text-primary">Your Victor Profile</h2>
                    <p class="mt-1 text-sm text-theme-text-secondary">
                        {{ props.authVictor.display_name }} is ready. Create an app from your profile to start queued native AIUX runs.
                    </p>
                </div>
                <Link
                    :href="route('victory-games.victors.show', props.authVictor.slug)"
                    class="rounded-lg border border-theme-border px-3 py-1.5 text-xs font-medium text-theme-text-muted transition hover:border-theme-accent hover:text-theme-accent"
                >
                    View profile
                </Link>
            </div>
        </div>

        <div v-if="victors.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <Link
                v-for="victor in victors"
                :key="victor.id"
                :href="route('victory-games.victors.show', victor.slug)"
                class="group bg-theme-card border border-theme-border rounded-xl p-5 flex flex-col gap-3 hover:border-theme-accent transition"
            >
                <!-- Avatar -->
                <div class="flex items-center gap-3">
                    <img
                        v-if="victor.avatar_url"
                        :src="victor.avatar_url"
                        :alt="victor.display_name"
                        class="w-12 h-12 rounded-full object-cover"
                    />
                    <div v-else class="w-12 h-12 rounded-full bg-theme-elevated flex items-center justify-center text-lg font-bold text-theme-text-muted">
                        {{ victor.display_name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                        <div class="font-bold text-theme-text-primary group-hover:text-theme-accent transition truncate">
                            {{ victor.display_name }}
                        </div>
                        <div class="text-xs text-theme-text-muted">
                            {{ victor.entries_count }} {{ victor.entries_count === 1 ? 'entry' : 'entries' }}
                        </div>
                    </div>
                </div>

                <!-- Best placement badge -->
                <div v-if="victor.best_placement" class="text-lg">
                    {{ placementEmoji[victor.best_placement] }}
                    <span class="text-xs text-theme-text-muted align-middle">Best finish</span>
                </div>

                <p v-if="victor.bio" class="text-xs text-theme-text-secondary line-clamp-2">{{ victor.bio }}</p>

                <!-- GitHub link -->
                <a
                    v-if="victor.github_url"
                    :href="victor.github_url"
                    target="_blank"
                    rel="noopener"
                    @click.stop
                    class="text-xs text-theme-accent hover:underline mt-auto"
                >
                    GitHub →
                </a>
            </Link>
        </div>

        <div v-else class="text-center py-24">
            <p class="text-theme-text-muted">No victors yet.</p>
        </div>
    </div>
</template>
