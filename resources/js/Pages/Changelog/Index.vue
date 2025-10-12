<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import marked from 'marked';

const props = defineProps({
    changelogs: Array,
    readChangelogIds: Array,
});

const isRead = (changelogId) => {
    return props.readChangelogIds.includes(changelogId);
};

const markAsRead = (changelog) => {
    router.post(route('changelog.read', { changelog: changelog.id }), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Changelog" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Changelog
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <div class="space-y-8 text-gray-300">
                        <div v-for="changelog in changelogs" :key="changelog.id">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-bold text-white">{{ new Date(Date.parse(changelog.release_date)).toLocaleDateString('en-US', { timeZone: 'UTC', year: 'numeric', month: 'long', day: 'numeric' }) }}</h3>
                                <button v-if="!isRead(changelog.id)" @click="markAsRead(changelog)" class="text-sm text-indigo-400 hover:text-indigo-300 flex items-center space-x-1">
                                    <span>Mark as Read</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                                <span v-else class="text-sm text-green-500 flex items-center space-x-1">
                                    <span>Read</span>
                                     <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </div>
                            <ul class="list-disc list-inside mt-2 pl-2 space-y-1 prose dark:prose-invert max-w-none">
                                <p v-for="(change, index) in changelog.changes" :key="index" v-html="marked.parse(change, { breaks: true })"></p>
                            </ul>
                        </div>
                         <div v-if="changelogs.length === 0" class="text-center">
                            <p>No updates have been posted yet.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
