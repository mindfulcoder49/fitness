<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    users: Array,
});
</script>

<template>
    <div class="bg-gray-800 p-6 shadow-sm sm:rounded-lg">
        <h3 class="text-lg font-semibold text-indigo-400">Leaderboard</h3>
        <ul class="mt-4 space-y-3">
            <li v-for="(user, index) in users" :key="user.id" class="flex items-center justify-between rounded-md bg-gray-900/50 p-3">
                <div class="flex items-center">
                    <span class="mr-4 font-bold text-gray-400">{{ index + 1 }}</span>
                    <Link :href="route('users.show', { user: user.name })" class="font-medium text-white hover:underline">
                        {{ user.name }}
                    </Link>
                </div>
                <span class="font-semibold text-indigo-400">{{ user.score }} pts</span>
            </li>
        </ul>
    </div>
</template>
