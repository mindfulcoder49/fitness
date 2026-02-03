<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    users: Array,
});
</script>

<template>
    <div class="bg-theme-card p-6 shadow-sm sm:rounded-lg">
        <h3 class="text-lg font-semibold text-theme-accent-text">Leaderboard</h3>
        <ul class="mt-4 space-y-3">
            <li v-for="(user, index) in users" :key="user.id" class="flex items-center justify-between rounded-md bg-theme-page/50 p-3">
                <div class="flex items-center">
                    <span class="mr-4 font-bold text-theme-text-muted">{{ index + 1 }}</span>
                    <Link :href="route('users.show', { user: user.name })" class="font-medium text-theme-text-primary hover:underline">
                        {{ user.name }}
                    </Link>
                </div>
                <span class="font-semibold text-theme-accent-text">{{ user.score }} pts</span>
            </li>
        </ul>
    </div>
</template>
