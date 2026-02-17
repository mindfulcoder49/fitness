<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="relative">
        <!-- Faded content teaser -->
        <div class="max-h-64 overflow-hidden relative">
            <slot />
            <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-theme-page"></div>
        </div>

        <!-- Login CTA -->
        <div class="mt-4 p-8 bg-theme-card border border-theme-border rounded-lg text-center">
            <svg class="w-12 h-12 mx-auto text-theme-text-muted mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <h3 class="text-lg font-semibold text-theme-text-primary mb-2">Members-Only Content</h3>
            <p class="text-sm text-theme-text-muted mb-4">Log in or register to read the full article.</p>
            <div class="flex items-center justify-center gap-4">
                <Link :href="route('login')" class="rounded-md bg-theme-accent px-4 py-2 text-sm font-semibold text-white hover:bg-theme-accent-hover transition">
                    Log In
                </Link>
                <Link :href="route('register')" class="rounded-md bg-theme-btn-secondary px-4 py-2 text-sm font-semibold text-theme-btn-secondary-text hover:bg-theme-btn-secondary-hover transition">
                    Register
                </Link>
            </div>
        </div>
    </div>
</template>
