<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GroupAdminPanel from '@/Components/Admin/GroupAdminPanel.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: Array,
    groups: Array,
});

const activeTab = ref('groups');
const openGroup = ref(null);

const toggleGroup = (groupId) => {
    openGroup.value = openGroup.value === groupId ? null : groupId;
};

const updateMediaPermissions = (user) => {
    router.patch(route('admin.users.update-media-permissions', { user: user.id }), {
        can_post_images: user.can_post_images,
        can_post_videos: user.can_post_videos,
    }, { preserveScroll: true });
};

const deleteUser = (user) => {
    if (confirm(`Are you sure you want to delete ${user.name}? This action cannot be undone.`)) {
        router.delete(route('admin.users.destroy', { user: user.id }), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-theme-text-primary">
                Site Administration
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-4 border-b border-theme-border">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button @click="activeTab = 'groups'" :class="[activeTab === 'groups' ? 'border-theme-accent text-theme-accent-text' : 'border-transparent text-theme-text-muted hover:border-theme-border hover:text-theme-text-secondary', 'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium']">Group Management</button>
                        <button @click="activeTab = 'users'" :class="[activeTab === 'users' ? 'border-theme-accent text-theme-accent-text' : 'border-transparent text-theme-text-muted hover:border-theme-border hover:text-theme-text-secondary', 'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium']">Global Users</button>
                    </nav>
                </div>

                <div class="overflow-hidden bg-theme-card shadow-sm sm:rounded-lg">
                    <div class="p-6 text-theme-text-primary">
                        <!-- Groups Management -->
                        <div v-if="activeTab === 'groups'" class="space-y-4">
                            <h3 class="text-2xl font-bold">All Groups</h3>
                             <div v-for="group in groups" :key="group.id" class="bg-theme-page/50 rounded-lg">
                                <button @click="toggleGroup(group.id)" class="w-full p-4 text-left flex justify-between items-center">
                                    <h4 class="text-xl font-semibold">{{ group.name }} <span class="text-sm text-theme-text-muted">({{ group.users.length }} members)</span></h4>
                                    <svg class="w-6 h-6 transform transition-transform" :class="{'rotate-180': openGroup === group.id}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div v-if="openGroup === group.id">
                                    <GroupAdminPanel :group="group" />
                                </div>
                            </div>
                        </div>

                        <!-- Global Users Table -->
                        <div v-if="activeTab === 'users'">
                            <h3 class="text-2xl font-bold">Global User Management</h3>
                            <div class="mt-6 overflow-x-auto">
                                <table class="min-w-full divide-y divide-theme-border">
                                    <thead class="bg-theme-elevated">
                                        <tr>
                                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold sm:pl-6">Name</th>
                                            <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold">Site Admin</th>
                                            <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold">Image Perms</th>
                                            <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold">Video Perms</th>
                                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-theme-border bg-theme-page">
                                        <tr v-for="user in users" :key="user.id">
                                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium sm:pl-6">{{ user.name }} ({{ user.username }})</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-center text-sm">
                                                <input type="checkbox" v-model="user.is_admin" class="h-4 w-4 rounded border-theme-border bg-theme-card text-theme-accent focus:ring-theme-accent-ring" />
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-center text-sm">
                                                <input type="checkbox" v-model="user.can_post_images" @change="updateMediaPermissions(user)" class="h-4 w-4 rounded border-theme-border bg-theme-card text-theme-accent focus:ring-theme-accent-ring" />
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-center text-sm">
                                                <input type="checkbox" v-model="user.can_post_videos" @change="updateMediaPermissions(user)" class="h-4 w-4 rounded border-theme-border bg-theme-card text-theme-accent focus:ring-theme-accent-ring" />
                                            </td>
                                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-2">
                                                <button @click="deleteUser(user)" class="text-red-500 hover:text-red-700">Delete</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
