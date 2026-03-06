<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const open = ref(false);
const toggleRef = ref(null);

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

const currentTheme = ref('light');

const getUser = () => usePage().props.auth?.user;

onMounted(() => {
    currentTheme.value = document.body.getAttribute('data-theme') || 'light';
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

const handleClickOutside = (e) => {
    if (toggleRef.value && !toggleRef.value.contains(e.target)) {
        open.value = false;
    }
};

const selectTheme = (themeId) => {
    currentTheme.value = themeId;
    document.body.setAttribute('data-theme', themeId);
    localStorage.setItem('theme', themeId);

    const user = getUser();
    if (user) {
        router.patch(route('profile.theme'), { theme: themeId }, {
            preserveScroll: true,
        });
    }

    open.value = false;
};
</script>

<template>
    <div ref="toggleRef" class="fixed bottom-6 right-6 z-50">
        <!-- Popover -->
        <div
            v-if="open"
            class="absolute bottom-14 right-0 w-64 rounded-lg border border-theme-border bg-theme-card p-3 shadow-xl"
        >
            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-theme-text-muted">Theme</p>
            <div class="grid grid-cols-3 gap-2">
                <button
                    v-for="theme in themes"
                    :key="theme.id"
                    @click="selectTheme(theme.id)"
                    class="flex flex-col items-center rounded-md p-2 transition-all"
                    :class="currentTheme === theme.id ? 'ring-2 ring-theme-accent-ring bg-theme-elevated' : 'hover:bg-theme-elevated'"
                >
                    <div class="flex space-x-0.5 mb-1">
                        <div
                            v-for="(color, i) in theme.colors"
                            :key="i"
                            class="w-4 h-4 rounded-full border border-gray-400/30"
                            :style="{ backgroundColor: color }"
                        ></div>
                    </div>
                    <span class="text-[10px] font-medium text-theme-text-primary leading-tight">{{ theme.name }}</span>
                </button>
            </div>
        </div>

        <!-- Toggle button -->
        <button
            @click="open = !open"
            class="flex h-11 w-11 items-center justify-center rounded-full border border-theme-border bg-theme-card text-theme-text-muted shadow-lg transition-all hover:text-theme-text-primary hover:shadow-xl"
            title="Change theme"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.098 19.902a3.75 3.75 0 005.304 0l6.401-6.402M6.75 21A3.75 3.75 0 013 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 003.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88M6.75 17.25h.008v.008H6.75v-.008z" />
            </svg>
        </button>
    </div>
</template>
