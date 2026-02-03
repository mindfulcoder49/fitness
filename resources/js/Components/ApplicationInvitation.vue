<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const user = computed(() => usePage().props.auth.user);
</script>

<template>
    <div class="bg-theme-card p-6 shadow-sm sm:rounded-lg">
        <h3 class="text-lg font-semibold text-theme-accent-text">Application Invitation</h3>
        <div class="mt-4">
            <div v-if="user.invitation_sent_at">
                <p class="text-theme-text-primary">Congratulations! You have been invited to apply for full membership.</p>
                <p class="text-sm text-theme-text-muted mt-1">Invited on: {{ new Date(user.invitation_sent_at).toLocaleDateString() }}</p>
                <a href="#" class="mt-4 inline-block rounded-md bg-theme-accent px-4 py-2 text-white hover:bg-theme-accent-hover">
                    Begin Application
                </a>
            </div>
            <div v-else>
                <p class="text-theme-text-muted">You have not received an invitation to apply yet. Keep up the great work!</p>
            </div>
        </div>
    </div>
</template>
