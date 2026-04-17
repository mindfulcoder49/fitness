<script setup>
import { computed, ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingMobileMenu = ref(false);
const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const victor = computed(() => page.props.auth?.victor ?? null);
const sections = computed(() => page.props.magazineSections ?? []);

// Editorial mode: magazine, VG, and community routes get section links + footer.
// Everything else (dashboard, groups, admin, help) gets app navigation.
const isEditorial = computed(() =>
    route().current('magazine.*') ||
    route().current('victory-games.*') ||
    route().current('community.*') ||
    route().current('groups.preview')
);
</script>

<template>
    <div class="min-h-screen bg-theme-page">

        <!-- ── Navigation ───────────────────────────────────────────── -->
        <nav class="border-b border-theme-border-subtle bg-theme-nav sticky top-0 z-50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between gap-4">

                    <!-- Logo -->
                    <div class="shrink-0">
                        <Link :href="route('magazine.home')">
                            <ApplicationLogo class="block h-9 w-auto fill-current text-theme-text-primary" />
                        </Link>
                    </div>

                    <!-- Desktop centre links -->
                    <div class="hidden md:flex items-center gap-6 flex-1 min-w-0">

                        <!-- Editorial: dynamic magazine sections -->
                        <template v-if="isEditorial">
                            <Link
                                v-for="section in sections"
                                :key="section.id"
                                :href="route('magazine.section', section.slug)"
                                class="shrink-0 text-sm font-medium text-theme-text-secondary hover:text-theme-text-primary transition"
                            >
                                {{ section.name }}
                            </Link>
                        </template>

                        <!-- App mode: core app links -->
                        <template v-else>
                            <NavLink
                                :href="route('magazine.home')"
                                :active="route().current('magazine.home')"
                            >
                                Home
                            </NavLink>
                            <NavLink
                                :href="route('dashboard')"
                                :active="route().current('dashboard')"
                            >
                                Dashboard
                            </NavLink>
                            <NavLink
                                v-if="$page.props.features.groups_enabled"
                                :href="route('groups.index')"
                                :active="route().current('groups.*')"
                            >
                                Groups
                            </NavLink>
                        </template>
                    </div>

                    <!-- Desktop right: VG + My Victor + user controls -->
                    <div class="hidden md:flex items-center gap-4 shrink-0">

                        <NavLink
                            :href="route('victory-games.home')"
                            :active="route().current('victory-games.*')"
                        >
                            🏅 Victory Games
                        </NavLink>

                        <NavLink
                            v-if="victor"
                            :href="route('victory-games.victors.show', victor.slug)"
                            :active="route().current('victory-games.victors.show') && route().params?.victor === victor.slug"
                        >
                            My Victor
                        </NavLink>

                        <!-- Authenticated: user dropdown -->
                        <Dropdown v-if="user" align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-md border border-transparent bg-theme-card px-3 py-2 text-sm font-medium leading-4 text-theme-text-muted hover:text-theme-text-secondary transition"
                                >
                                    {{ user.name }}
                                    <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </template>
                            <template #content>
                                <DropdownLink v-if="victor" :href="route('victory-games.victors.show', victor.slug)">
                                    My Victor
                                </DropdownLink>
                                <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                <DropdownLink :href="route('help')">Help</DropdownLink>
                                <DropdownLink v-if="user.is_admin" :href="route('admin.dashboard')">Admin</DropdownLink>
                                <DropdownLink v-if="user.is_admin" :href="route('admin.victory-games.index')">VG Admin</DropdownLink>
                                <DropdownLink v-if="user.is_admin" :href="route('admin.magazine.articles.index')">Magazine</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                            </template>
                        </Dropdown>

                        <!-- Guest: login / register -->
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-sm font-medium text-theme-text-secondary hover:text-theme-text-primary transition"
                            >
                                Log In
                            </Link>
                            <Link
                                v-if="$page.props.features.groups_enabled"
                                :href="route('register')"
                                class="rounded-md bg-theme-accent px-3 py-1.5 text-sm font-semibold text-white hover:bg-theme-accent-hover transition"
                            >
                                Register
                            </Link>
                        </template>
                    </div>

                    <!-- Mobile hamburger -->
                    <div class="md:hidden -me-2 flex items-center">
                        <button
                            @click="showingMobileMenu = !showingMobileMenu"
                            class="inline-flex items-center justify-center rounded-md p-2 text-theme-text-muted hover:bg-theme-elevated hover:text-theme-text-secondary transition"
                        >
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path
                                    :class="{ hidden: showingMobileMenu, 'inline-flex': !showingMobileMenu }"
                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    :class="{ hidden: !showingMobileMenu, 'inline-flex': showingMobileMenu }"
                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div
                :class="{ block: showingMobileMenu, hidden: !showingMobileMenu }"
                class="md:hidden border-t border-theme-border-subtle bg-theme-card"
            >
                <!-- Primary links -->
                <div class="space-y-1 pb-3 pt-2">
                    <template v-if="isEditorial">
                        <ResponsiveNavLink
                            v-for="section in sections"
                            :key="section.id"
                            :href="route('magazine.section', section.slug)"
                        >
                            {{ section.name }}
                        </ResponsiveNavLink>
                    </template>
                    <template v-else>
                        <ResponsiveNavLink
                            :href="route('magazine.home')"
                            :active="route().current('magazine.home')"
                        >
                            Home
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                        >
                            Dashboard
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="$page.props.features.groups_enabled"
                            :href="route('groups.index')"
                            :active="route().current('groups.*')"
                        >
                            Groups
                        </ResponsiveNavLink>
                    </template>

                    <ResponsiveNavLink
                        :href="route('victory-games.home')"
                        :active="route().current('victory-games.*')"
                    >
                        🏅 Victory Games
                    </ResponsiveNavLink>

                    <ResponsiveNavLink
                        v-if="victor"
                        :href="route('victory-games.victors.show', victor.slug)"
                    >
                        My Victor
                    </ResponsiveNavLink>
                </div>

                <!-- User section -->
                <div class="border-t border-theme-border pb-1 pt-4">
                    <template v-if="user">
                        <div class="px-4 mb-3">
                            <div class="text-base font-medium text-theme-text-primary">{{ user.name }}</div>
                            <div class="text-sm font-medium text-theme-text-muted">{{ user.email }}</div>
                        </div>
                        <div class="space-y-1">
                            <ResponsiveNavLink v-if="victor" :href="route('victory-games.victors.show', victor.slug)">
                                My Victor
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('profile.edit')">Profile</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('help')">Help</ResponsiveNavLink>
                            <ResponsiveNavLink v-if="user.is_admin" :href="route('admin.dashboard')">Admin</ResponsiveNavLink>
                            <ResponsiveNavLink v-if="user.is_admin" :href="route('admin.victory-games.index')">VG Admin</ResponsiveNavLink>
                            <ResponsiveNavLink v-if="user.is_admin" :href="route('admin.magazine.articles.index')">Magazine</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">Log Out</ResponsiveNavLink>
                        </div>
                    </template>
                    <template v-else>
                        <div class="space-y-1">
                            <ResponsiveNavLink :href="route('login')">Log In</ResponsiveNavLink>
                            <ResponsiveNavLink v-if="$page.props.features.groups_enabled" :href="route('register')">
                                Register
                            </ResponsiveNavLink>
                        </div>
                    </template>
                </div>
            </div>
        </nav>

        <!-- ── Optional sticky page header (app pages only) ─────────── -->
        <header
            v-if="$slots.header"
            class="bg-theme-header shadow sticky top-16 z-30"
        >
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- ── Main content ──────────────────────────────────────────── -->
        <main>
            <slot />
        </main>

        <!-- ── Footer (editorial pages only) ────────────────────────── -->
        <footer v-if="isEditorial" class="border-t border-theme-border-subtle bg-theme-card mt-16">
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
                        <Link
                            v-if="$page.props.features.groups_enabled"
                            :href="route('groups.index')"
                            class="hover:text-theme-text-primary transition"
                        >
                            Groups
                        </Link>
                        <Link
                            :href="route('victory-games.home')"
                            class="hover:text-theme-text-primary transition"
                        >
                            Victory Games
                        </Link>
                    </div>
                </div>
                <div class="mt-8 text-center text-xs text-theme-text-faint">
                    &copy; {{ new Date().getFullYear() }} {{ $page.props.appName }}. All rights reserved.
                </div>
            </div>
        </footer>

    </div>

    <ThemeToggle />
</template>
