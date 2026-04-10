<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingMobileMenu = ref(false);
const page = usePage();
const user = page.props.auth?.user;
const sections = page.props.magazineSections || [];
</script>

<template>
    <div class="min-h-screen bg-theme-page text-theme-text-primary">
        <!-- Magazine Navbar -->
        <nav class="border-b border-theme-border-subtle bg-theme-nav sticky top-0 z-50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <!-- Logo + Site Name -->
                    <div class="flex items-center">
                        <Link :href="route('magazine.home')" class="flex items-center gap-2">
                            <ApplicationLogo class="h-8 w-auto" />
                            <span class="text-lg font-bold text-theme-text-primary hidden sm:block">{{ $page.props.appName }}</span>
                        </Link>
                    </div>

                    <!-- Desktop Nav -->
                    <div class="hidden md:flex items-center gap-6">
                        <Link
                            v-for="section in sections"
                            :key="section.id"
                            :href="route('magazine.section', section.slug)"
                            class="text-sm font-medium text-theme-text-secondary hover:text-theme-text-primary transition"
                        >
                            {{ section.name }}
                        </Link>

                        <!-- Victory Games nav link -->
                        <div class="w-px h-5 bg-theme-border"></div>
                        <Link
                            :href="route('victory-games.home')"
                            class="text-sm font-medium text-theme-text-secondary hover:text-theme-text-primary transition flex items-center gap-1"
                        >
                            <span>🏅</span> Victory Games
                        </Link>

                        <template v-if="$page.props.features.groups_enabled">
                            <div class="w-px h-5 bg-theme-border"></div>
                            <Link
                                :href="route('groups.index')"
                                class="text-sm font-medium text-theme-text-secondary hover:text-theme-text-primary transition"
                            >
                                Groups
                            </Link>
                        </template>

                        <Link
                            v-if="$page.props.features.groups_enabled"
                            :href="user ? route('dashboard') : route('community.welcome')"
                            class="text-sm font-medium text-theme-text-secondary hover:text-theme-text-primary transition"
                        >
                            Community
                        </Link>

                        <template v-if="user">
                            <Link
                                :href="route('dashboard')"
                                class="text-sm font-medium text-theme-text-secondary hover:text-theme-text-primary transition"
                            >
                                {{ user.name }}
                            </Link>
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="text-sm font-medium text-theme-text-muted hover:text-theme-text-primary transition"
                            >
                                Log Out
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="route('login')" class="text-sm font-medium text-theme-text-secondary hover:text-theme-text-primary transition">
                                Log In
                            </Link>
                            <Link v-if="$page.props.features.groups_enabled" :href="route('register')" class="rounded-md bg-theme-accent px-3 py-1.5 text-sm font-semibold text-white hover:bg-theme-accent-hover transition">
                                Register
                            </Link>
                        </template>
                    </div>

                    <!-- Mobile Hamburger -->
                    <div class="md:hidden">
                        <button
                            @click="showingMobileMenu = !showingMobileMenu"
                            class="p-2 text-theme-text-muted hover:text-theme-text-primary transition"
                        >
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path v-if="!showingMobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div v-if="showingMobileMenu" class="md:hidden border-t border-theme-border-subtle bg-theme-card">
                <div class="px-4 py-3 space-y-2">
                    <Link
                        v-for="section in sections"
                        :key="section.id"
                        :href="route('magazine.section', section.slug)"
                        class="block text-sm font-medium text-theme-text-secondary hover:text-theme-text-primary py-1"
                    >
                        {{ section.name }}
                    </Link>

                    <template v-if="$page.props.features.groups_enabled">
                        <div class="border-t border-theme-border-subtle my-2"></div>
                        <Link
                            :href="route('groups.index')"
                            class="block text-sm font-medium text-theme-text-secondary hover:text-theme-text-primary py-1"
                        >
                            Groups
                        </Link>
                    </template>

                    <Link
                        v-if="$page.props.features.groups_enabled"
                        :href="user ? route('dashboard') : route('community.welcome')"
                        class="block text-sm font-medium text-theme-text-secondary hover:text-theme-text-primary py-1"
                    >
                        Community
                    </Link>

                    <template v-if="user">
                        <Link :href="route('dashboard')" class="block text-sm font-medium text-theme-text-secondary hover:text-theme-text-primary py-1">
                            {{ user.name }}
                        </Link>
                        <Link :href="route('logout')" method="post" as="button" class="block text-sm font-medium text-theme-text-muted hover:text-theme-text-primary py-1">
                            Log Out
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="block text-sm font-medium text-theme-text-secondary hover:text-theme-text-primary py-1">
                            Log In
                        </Link>
                        <Link v-if="$page.props.features.groups_enabled" :href="route('register')" class="block text-sm font-medium text-theme-accent hover:text-theme-accent-hover py-1">
                            Register
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main>
            <slot />
        </main>

        <!-- Footer -->
        <footer class="border-t border-theme-border-subtle bg-theme-card mt-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
                <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="flex items-center gap-3">
                        <ApplicationLogo class="h-8 w-auto" />
                        <span class="text-sm text-theme-text-secondary">{{ $page.props.appName }}</span>
                    </div>
                    <div class="flex flex-wrap gap-6 text-sm text-theme-text-muted">
                        <Link
                            v-for="section in sections"
                            :key="section.id"
                            :href="route('magazine.section', section.slug)"
                            class="hover:text-theme-text-primary transition"
                        >
                            {{ section.name }}
                        </Link>
                        <Link v-if="$page.props.features.groups_enabled" :href="route('groups.index')" class="hover:text-theme-text-primary transition">
                            Groups
                        </Link>
                        <Link v-if="$page.props.features.groups_enabled" :href="user ? route('dashboard') : route('community.welcome')" class="hover:text-theme-text-primary transition">
                            Community
                        </Link>
                    </div>
                </div>
                <div class="mt-8 text-center text-xs text-theme-text-faint">
                    &copy; {{ new Date().getFullYear() }} {{ $page.props.appName }}. All rights reserved.
                </div>
            </div>
        </footer>

        <ThemeToggle />
    </div>
</template>
