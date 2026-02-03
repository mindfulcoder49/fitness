<script setup>
import { usePage, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const user = usePage().props.auth.user;
const currentTheme = ref(user.theme || 'dark');

const themes = [
    { id: 'dark', name: 'Dark', colors: ['#111827', '#1F2937', '#6366F1'] },
    { id: 'light', name: 'Light', colors: ['#F3F4F6', '#FFFFFF', '#6366F1'] },
    { id: 'rose', name: 'Rose', colors: ['#1F0C18', '#2D1424', '#F43F5E'] },
    { id: 'ocean', name: 'Ocean', colors: ['#080F23', '#0F1734', '#06B6D4'] },
    { id: 'forest', name: 'Forest', colors: ['#0A1A12', '#14281E', '#10B981'] },
    { id: 'sunset', name: 'Sunset', colors: ['#FFF7ED', '#FFFFFF', '#EA580C'] },
    { id: 'lavender', name: 'Lavender', colors: ['#F5F3FF', '#FFFFFF', '#7C3AED'] },
    { id: 'sky', name: 'Sky', colors: ['#F0F9FF', '#FFFFFF', '#0284C7'] },
    { id: 'sakura', name: 'Sakura', colors: ['#FFF1F2', '#FFFFFF', '#EC4899'] },
];

const selectTheme = (themeId) => {
    currentTheme.value = themeId;
    document.body.setAttribute('data-theme', themeId);
    localStorage.setItem('theme', themeId);
    router.patch(route('profile.theme'), { theme: themeId }, {
        preserveScroll: true,
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-theme-text-primary">
                Theme
            </h2>

            <p class="mt-1 text-sm text-theme-text-muted">
                Choose a color theme for the application.
            </p>
        </header>

        <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
            <button
                v-for="theme in themes"
                :key="theme.id"
                @click="selectTheme(theme.id)"
                class="relative flex flex-col items-center p-4 rounded-lg border-2 transition-all"
                :class="currentTheme === theme.id ? 'border-theme-accent ring-2 ring-theme-accent-ring' : 'border-theme-border hover:border-theme-text-muted'"
            >
                <div class="flex space-x-1 mb-2">
                    <div
                        v-for="(color, i) in theme.colors"
                        :key="i"
                        class="w-6 h-6 rounded-full border border-gray-600"
                        :style="{ backgroundColor: color }"
                    ></div>
                </div>
                <span class="text-sm font-medium text-theme-text-primary">{{ theme.name }}</span>
                <div v-if="currentTheme === theme.id" class="absolute top-1 right-1">
                    <svg class="w-5 h-5 text-theme-accent-text" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
            </button>
        </div>
    </section>
</template>
