<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';

defineOptions({ layout: AppLayout });

const page = usePage();
const user = computed(() => page.props.auth?.user);

defineProps({
    group: Object,
});
</script>

<template>
    <Head :title="group.name" />

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-theme-card border border-theme-border rounded-lg overflow-hidden">
                <!-- Group Header -->
                <div class="p-8 border-b border-theme-border">
                    <h1 class="text-3xl font-bold text-theme-text-primary">{{ group.name }}</h1>
                    <p v-if="group.description" class="mt-4 text-theme-text-secondary leading-relaxed">{{ group.description }}</p>
                </div>

                <!-- Group Details -->
                <div class="p-8 space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-theme-text-muted w-28">Created by</span>
                        <span class="text-sm text-theme-text-primary">{{ group.creator.name }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-theme-text-muted w-28">Members</span>
                        <span class="text-sm text-theme-text-primary">{{ group.users_count }}</span>
                    </div>
                    <div v-if="group.min_members && !group.launched_at" class="flex items-center gap-3">
                        <span class="text-sm font-medium text-theme-text-muted w-28">Status</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-600 text-yellow-100">
                            Gathering Members ({{ group.users_count }} / {{ group.min_members }})
                        </span>
                    </div>
                    <div v-else-if="group.launched_at" class="flex items-center gap-3">
                        <span class="text-sm font-medium text-theme-text-muted w-28">Status</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-theme-success/20 text-theme-success">
                            Active
                        </span>
                    </div>
                </div>

                <!-- CTA -->
                <div class="p-8 bg-theme-elevated border-t border-theme-border text-center">
                    <template v-if="user">
                        <p class="text-sm text-theme-text-muted mb-4">Join this group to see posts, meetups, and connect with members.</p>
                        <Link :href="route('groups.index')" class="inline-flex items-center justify-center px-6 py-2.5 bg-theme-accent text-white rounded-md text-sm font-semibold hover:bg-theme-accent-hover transition">
                            Back to Groups
                        </Link>
                    </template>
                    <template v-else>
                        <p class="text-sm text-theme-text-muted mb-4">Log in or register to join this group and connect with its members.</p>
                        <div class="flex items-center justify-center gap-4">
                            <Link :href="route('login')" class="inline-flex items-center justify-center px-6 py-2.5 bg-theme-accent text-white rounded-md text-sm font-semibold hover:bg-theme-accent-hover transition">
                                Log In
                            </Link>
                            <Link :href="route('register')" class="inline-flex items-center justify-center px-6 py-2.5 bg-theme-btn-secondary text-theme-btn-secondary-text rounded-md text-sm font-semibold hover:bg-theme-btn-secondary-hover transition">
                                Register
                            </Link>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Back link -->
            <div class="mt-6 text-center">
                <Link :href="route('groups.index')" class="text-sm text-theme-link hover:text-theme-link-hover transition">
                    &larr; Browse all groups
                </Link>
            </div>
        </div>
    </div>
</template>
