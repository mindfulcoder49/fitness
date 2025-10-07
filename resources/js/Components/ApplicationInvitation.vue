<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const user = computed(() => usePage().props.auth.user);
</script>

<template>
    <div class="bg-gray-800 p-6 shadow-sm sm:rounded-lg">
        <h3 class="text-lg font-semibold text-indigo-400">Application Invitation</h3>
        <div class="mt-4">
            <div v-if="user.invitation_sent_at">
                <p class="text-white">Congratulations! You have been invited to apply for full membership.</p>
                <p class="text-sm text-gray-400 mt-1">Invited on: {{ new Date(user.invitation_sent_at).toLocaleDateString() }}</p>
                <a href="#" class="mt-4 inline-block rounded-md bg-indigo-500 px-4 py-2 text-white hover:bg-indigo-400">
                    Begin Application
                </a>
            </div>
            <div v-else>
                <p class="text-gray-400">You have not received an invitation to apply yet. Keep up the great work!</p>
            </div>
        </div>
    </div>
</template>
