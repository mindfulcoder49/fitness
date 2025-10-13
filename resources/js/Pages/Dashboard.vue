<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import NotificationsPanel from '@/Components/NotificationsPanel.vue';
import TodoListPanel from '@/Components/TodoListPanel.vue';
import CreateGroupForm from '@/Components/CreateGroupForm.vue';

const props = defineProps({
    groups: Array,
    notifications: Object,
    notificationsLastCheckedAt: String,
    todos: Array,
});

const showNotifications = ref(false);
const showTodos = ref(false);
const showCreateGroupForm = ref(false);

const newNotificationCount = computed(() => {
    if (!props.notifications) return 0;
    const lastChecked = props.notificationsLastCheckedAt;
    const newPosts = (props.notifications.posts || []).filter(p => !lastChecked || new Date(p.created_at) > new Date(lastChecked)).length;
    const newComments = (props.notifications.commentsOnUserPosts || []).filter(c => !lastChecked || new Date(c.created_at) > new Date(lastChecked)).length;
    const newLikes = (props.notifications.likesOnUserContent || []).filter(l => !lastChecked || new Date(l.created_at) > new Date(lastChecked)).length;
    const newChangelogs = (props.notifications.changelogs || []).length;
    return newPosts + newComments + newLikes + newChangelogs;
});

const todoCount = computed(() => props.todos?.length || 0);

const toggleNotifications = () => {
    showTodos.value = false;
    showNotifications.value = !showNotifications.value;
    if (showNotifications.value) {
        router.post(route('notifications.mark-as-read'), {}, {
            preserveScroll: true,
            onSuccess: () => {
                usePage().props.notificationsLastCheckedAt = new Date().toISOString();
            }
        });
    }
};

const toggleTodos = () => {
    showNotifications.value = false;
    showTodos.value = !showTodos.value;
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Your Groups
                </h2>
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <button @click="toggleTodos" class="relative p-2 bg-gray-700 rounded-full text-gray-300 hover:text-white focus:outline-none">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        <span v-if="todoCount > 0" class="absolute top-0 right-0 block h-4 w-4 transform -translate-y-1/2 translate-x-1/2 rounded-full text-white bg-amber-500 text-xs flex items-center justify-center">
                            {{ todoCount }}
                        </span>
                    </button>
                    <button @click="toggleNotifications" class="relative p-2 bg-gray-700 rounded-full text-gray-300 hover:text-white focus:outline-none">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span v-if="newNotificationCount > 0" class="absolute top-0 right-0 block h-4 w-4 transform -translate-y-1/2 translate-x-1/2 rounded-full text-white bg-red-500 text-xs flex items-center justify-center">
                            {{ newNotificationCount }}
                        </span>
                    </button>
                </div>
            </div>
        </template>

        <NotificationsPanel v-if="showNotifications" :notifications="notifications" :last-checked="notificationsLastCheckedAt" @close="showNotifications = false" />
        <TodoListPanel v-if="showTodos" :todos="todos" @close="showTodos = false" />
        <CreateGroupForm :show="showCreateGroupForm" @close="showCreateGroupForm = false" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-2xl font-bold">Select a Group</h3>
                        <p class="mt-2 text-gray-400">
                            Choose a group to view its feed, tasks, and leaderboard.
                        </p>
                        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <Link v-for="group in groups" :key="group.id" :href="route('groups.show', group.id)" class="block p-6 bg-gray-700 rounded-lg hover:bg-gray-600 transition">
                                <h4 class="text-xl font-semibold text-white">{{ group.name }}</h4>
                                <p class="mt-2 text-gray-300">{{ group.description }}</p>
                                <div class="mt-4 text-sm text-gray-400">
                                    Created by: {{ group.creator.name }}
                                </div>
                            </Link>
                            <!-- Add a card for creating/joining groups -->
                             <div class="flex items-center justify-center p-6 bg-gray-700/50 border-2 border-dashed border-gray-600 rounded-lg">
                                <div class="text-center">
                                    <p class="text-gray-400">Want to start a new community?</p>
                                    <button @click="showCreateGroupForm = true" class="mt-2 text-indigo-400 hover:text-indigo-300 font-semibold">Create a Group</button>
                                    <p class="mt-4 text-gray-400">Or find one to join.</p>
                                    <Link :href="route('groups.index')" class="mt-2 text-indigo-400 hover:text-indigo-300 font-semibold">Browse Public Groups</Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
