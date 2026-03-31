<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import NotificationsPanel from '@/Components/NotificationsPanel.vue';
import TodoListPanel from '@/Components/TodoListPanel.vue';
import CreateGroupForm from '@/Components/CreateGroupForm.vue';
import axios from 'axios';

const page = usePage();

const props = defineProps({
    groups: Array,
});

const todos = ref([]);
const notifications = ref({});
const notificationsLastChecked = ref(null);
const showNotifications = ref(false);
const showTodos = ref(false);
const showCreateGroupForm = ref(false);

const newNotificationCount = computed(() => {
    if (!notifications.value) return 0;
    const lastChecked = notificationsLastChecked.value;
    const newPosts = (notifications.value.posts || []).filter(p => !lastChecked || new Date(p.created_at) > new Date(lastChecked)).length;
    const newComments = (notifications.value.commentsOnUserPosts || []).filter(c => !lastChecked || new Date(c.created_at) > new Date(lastChecked)).length;
    const newLikes = (notifications.value.likesOnUserContent || []).filter(l => !lastChecked || new Date(l.created_at) > new Date(lastChecked)).length;
    const newChangelogs = (notifications.value.changelogs || []).filter(c => !lastChecked || new Date(c.release_date) > new Date(lastChecked)).length;
    return newPosts + newComments + newLikes + newChangelogs;
});

const todoCount = computed(() => todos.value?.length || 0);

const fetchTodos = async () => {
    try {
        const response = await axios.get(route('todos.index'));
        todos.value = response.data;
    } catch (error) {
        console.error('Failed to fetch todos:', error);
    }
};

const fetchNotifications = async () => {
    try {
        const response = await axios.get(route('notifications.index'));
        notifications.value = response.data;
        notificationsLastChecked.value = response.data.lastChecked;
    } catch (error) {
        console.error('Failed to fetch notifications:', error);
    }
};

onMounted(() => {
    fetchTodos();
    fetchNotifications();
});

const toggleNotifications = () => {
    showTodos.value = false;
    showNotifications.value = !showNotifications.value;
    if (showNotifications.value) {
        router.post(route('notifications.mark-as-read'), {}, {
            preserveScroll: true,
            onSuccess: () => {
                notificationsLastChecked.value = new Date().toISOString();
            }
        });
    }
};

const toggleTodos = () => {
    showNotifications.value = false;
    showTodos.value = !showTodos.value;
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
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-theme-text-primary leading-tight">
                    {{ page.props.features.groups_enabled ? 'Your Groups' : 'Dashboard' }}
                </h2>
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <button @click="toggleTodos" class="relative p-2 bg-theme-elevated rounded-full text-theme-text-secondary hover:text-theme-text-primary focus:outline-none">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        <span v-if="todoCount > 0" class="absolute top-0 right-0 block h-4 w-4 transform -translate-y-1/2 translate-x-1/2 rounded-full text-white bg-amber-500 text-xs flex items-center justify-center">
                            {{ todoCount }}
                        </span>
                    </button>
                    <button @click="toggleNotifications" class="relative p-2 bg-theme-elevated rounded-full text-theme-text-secondary hover:text-theme-text-primary focus:outline-none">
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

        <NotificationsPanel v-if="showNotifications" :notifications="notifications" :last-checked="notificationsLastChecked" @close="showNotifications = false" />
        <TodoListPanel v-if="showTodos" :todos="todos" @close="showTodos = false" @refresh="fetchTodos" />
        <CreateGroupForm v-if="page.props.features.groups_enabled" :show="showCreateGroupForm" @close="showCreateGroupForm = false" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-if="page.props.flash?.success" class="mb-4 flex items-center justify-between bg-green-900 border-l-4 border-green-600 text-green-200 p-4" role="alert">
                    <p>{{ page.props.flash.success }}</p>
                    <button @click="page.props.flash.success = null" class="text-xl font-bold leading-none">&times;</button>
                </div>
                <div v-if="page.props.flash?.error" class="mb-4 flex items-center justify-between bg-red-900 border-l-4 border-red-600 text-red-200 p-4" role="alert">
                    <p>{{ page.props.flash.error }}</p>
                    <button @click="page.props.flash.error = null" class="text-xl font-bold leading-none">&times;</button>
                </div>
                <div v-if="page.props.features.groups_enabled" class="bg-theme-card overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-theme-text-primary">
                        <h3 class="text-2xl font-bold">Select a Group</h3>
                        <p class="mt-2 text-theme-text-muted">
                            Choose a group to view its feed and participate, or create your own.
                        </p>
                        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="group in groups" :key="group.id" class="flex flex-col justify-between p-6 bg-theme-elevated rounded-lg">
                                <div>
                                    <h4 class="text-xl font-semibold text-theme-text-primary">{{ group.name }}</h4>
                                    <p class="mt-2 text-theme-text-secondary">{{ group.description }}</p>
                                    <div class="mt-4 text-sm text-theme-text-muted">
                                        Created by: {{ group.creator.name }}
                                    </div>
                                </div>
                                <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <Link :href="route('groups.show', group.id)" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-theme-accent border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-theme-accent-hover focus:outline-none focus:ring-2 focus:ring-theme-accent-ring focus:ring-offset-2 transition ease-in-out duration-150">
                                        View
                                    </Link>
                                    <button @click="leaveGroup(group)" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-theme-danger border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-theme-danger-hover focus:outline-none focus:ring-2 focus:ring-theme-danger focus:ring-offset-2 transition ease-in-out duration-150">
                                        Leave
                                    </button>
                                </div>
                            </div>
                            <!-- Add a card for creating/joining groups -->
                             <div class="flex items-center justify-center p-6 bg-theme-elevated/50 border-2 border-dashed border-theme-border rounded-lg">
                                <div class="text-center">
                                    <p class="text-theme-text-muted">Want to start a new community?</p>
                                    <button @click="showCreateGroupForm = true" class="mt-2 text-theme-accent-text hover:text-theme-accent-text font-semibold">Create a Group</button>
                                    <p class="mt-4 text-theme-text-muted">Or find one to join.</p>
                                    <Link :href="route('groups.index')" class="mt-2 text-theme-accent-text hover:text-theme-accent-text font-semibold">Browse Public Groups</Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="bg-theme-card overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-theme-text-primary">
                        <h3 class="text-2xl font-bold">Welcome</h3>
                        <p class="mt-2 text-theme-text-muted">Use the navigation above to explore the magazine.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
