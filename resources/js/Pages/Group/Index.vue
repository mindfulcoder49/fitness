<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const page = usePage();

defineProps({
    groups: Array,
});

const joinGroup = (groupId) => {
    router.post(route('groups.join', groupId), {}, {
        preserveScroll: true,
    });
};

const leaveGroup = (group) => {
    if (confirm(`Are you sure you want to leave the group "${group.name}"?`)) {
        router.post(route('groups.leave', group.id), {}, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Public Groups" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Public Groups
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-if="page.props.flash?.error" class="mb-4 flex items-center justify-between bg-red-100 border-l-4 border-red-500 text-red-700 p-4 dark:bg-red-900 dark:border-red-600 dark:text-red-200" role="alert">
                    <p>{{ page.props.flash.error }}</p>
                    <button @click="page.props.flash.error = null" class="text-xl font-bold leading-none">&times;</button>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-2xl font-bold">Find a Community</h3>
                        <p class="mt-2 text-gray-400">
                            Browse public groups and join one that fits your interests.
                        </p>
                        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="group in groups" :key="group.id" class="flex flex-col justify-between p-6 bg-gray-700 rounded-lg transition">
                                <div>
                                    <h4 class="text-xl font-semibold text-white">{{ group.name }}</h4>
                                    <p class="mt-2 text-gray-300 h-24 overflow-y-auto">{{ group.description }}</p>
                                    <div class="mt-4 text-sm text-gray-400">
                                        <p>Created by: {{ group.creator.name }}</p>
                                        <p>{{ group.users_count }} member(s)</p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <div v-if="group.is_member" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                        <Link :href="route('groups.show', group.id)" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-gray-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                            View
                                        </Link>
                                        <button @click="leaveGroup(group)" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                            Leave
                                        </button>
                                    </div>
                                    <PrimaryButton v-else @click="joinGroup(group.id)" class="w-full justify-center">
                                        Join Group
                                    </PrimaryButton>
                                </div>
                            </div>
                        </div>
                         <div v-if="groups.length === 0" class="text-center py-12 text-gray-500">
                            <p>There are no public groups available right now.</p>
                            <p>Why not create one?</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
