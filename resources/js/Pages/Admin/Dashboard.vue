<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: Array,
    posts: Array,
    likes: Array,
});

const activeTab = ref('users');
const roles = ['prospective', 'initiate', 'full_member', 'admin'];

const updateRole = (user, newRole) => {
    if (user.role !== newRole) {
        router.patch(route('admin.users.update-role', { user: user.id }), {
            role: newRole,
        }, {
            preserveScroll: true,
        });
    }
};

const updateMediaPermissions = (user) => {
    router.patch(route('admin.users.update-media-permissions', { user: user.id }), {
        can_post_images: user.can_post_images,
        can_post_videos: user.can_post_videos,
    }, {
        preserveScroll: true,
    });
};

const sendInvitation = (user) => {
    if (confirm(`Are you sure you want to send an application invitation to ${user.name}?`)) {
        router.post(route('admin.users.send-invitation', { user: user.id }), {}, {
            preserveScroll: true,
        });
    }
};

const deleteItem = (type, id) => {
    if (confirm(`Are you sure you want to delete this ${type}? This action cannot be undone.`)) {
        let routeName;
        let param;
        if (type === 'user') {
            routeName = 'admin.users.destroy';
            param = { user: id };
        } else if (type === 'post') {
            routeName = 'admin.posts.destroy';
            param = { post: id };
        } else if (type === 'like') {
            routeName = 'admin.likes.destroy';
            param = { like: id };
        }
        
        router.delete(route(routeName, param), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Admin Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-4 border-b border-gray-700">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button @click="activeTab = 'users'" :class="[activeTab === 'users' ? 'border-indigo-500 text-indigo-400' : 'border-transparent text-gray-400 hover:border-gray-500 hover:text-gray-300', 'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium']">Users</button>
                        <button @click="activeTab = 'posts'" :class="[activeTab === 'posts' ? 'border-indigo-500 text-indigo-400' : 'border-transparent text-gray-400 hover:border-gray-500 hover:text-gray-300', 'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium']">Posts</button>
                        <button @click="activeTab = 'likes'" :class="[activeTab === 'likes' ? 'border-indigo-500 text-indigo-400' : 'border-transparent text-gray-400 hover:border-gray-500 hover:text-gray-300', 'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium']">Likes</button>
                    </nav>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <!-- Users Table -->
                        <div v-if="activeTab === 'users'">
                            <h3 class="text-2xl font-bold">User Management & Engagement</h3>
                            <div class="mt-6 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-700">
                                    <thead class="bg-gray-700">
                                        <tr>
                                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold sm:pl-6">Name</th>
                                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold">Role</th>
                                            <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold">Image Perms</th>
                                            <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold">Video Perms</th>
                                            <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold">Posts</th>
                                            <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold">Likes</th>
                                            <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold">Comments</th>
                                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800 bg-gray-900">
                                        <tr v-for="user in users" :key="user.id">
                                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium sm:pl-6">{{ user.name }}</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-300">
                                                <select :value="user.role" @change="updateRole(user, $event.target.value)" class="block w-full rounded-md border-gray-600 bg-gray-800 py-1.5 pl-3 pr-10 text-white focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
                                                    <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                                                </select>
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-center text-sm text-gray-300">
                                                <input type="checkbox" v-model="user.can_post_images" @change="updateMediaPermissions(user)" class="h-4 w-4 rounded border-gray-600 bg-gray-800 text-indigo-600 focus:ring-indigo-600" />
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-center text-sm text-gray-300">
                                                <input type="checkbox" v-model="user.can_post_videos" @change="updateMediaPermissions(user)" class="h-4 w-4 rounded border-gray-600 bg-gray-800 text-indigo-600 focus:ring-indigo-600" />
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-center text-sm text-gray-300">{{ user.posts_count }}</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-center text-sm text-gray-300">{{ user.likes_count }}</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-center text-sm text-gray-300">{{ user.comments_count }}</td>
                                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-2">
                                                <button v-if="!user.invitation_sent_at" @click="sendInvitation(user)" class="text-indigo-400 hover:text-indigo-600">Invite</button>
                                                <span v-else class="text-xs text-gray-500">Invited</span>
                                                <button @click="deleteItem('user', user.id)" class="text-red-500 hover:text-red-700">Delete</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Posts Table -->
                        <div v-if="activeTab === 'posts'">
                             <h3 class="text-2xl font-bold">Post Management</h3>
                             <div class="mt-6 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-700">
                                    <thead class="bg-gray-700">
                                        <tr>
                                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold sm:pl-6">Author</th>
                                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold">Content</th>
                                            <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold">Likes</th>
                                            <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold">Comments</th>
                                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800 bg-gray-900">
                                        <tr v-for="post in posts" :key="post.id">
                                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium sm:pl-6">{{ post.user.name }}</td>
                                            <td class="px-3 py-4 text-sm text-gray-300 max-w-md truncate">{{ post.content }}</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-center text-sm text-gray-300">{{ post.likes_count }}</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-center text-sm text-gray-300">{{ post.comments_count }}</td>
                                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                                <button @click="deleteItem('post', post.id)" class="text-red-500 hover:text-red-700">Delete</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Likes Table -->
                        <div v-if="activeTab === 'likes'">
                             <h3 class="text-2xl font-bold">Like Management</h3>
                             <div class="mt-6 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-700">
                                    <thead class="bg-gray-700">
                                        <tr>
                                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold sm:pl-6">User</th>
                                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold">Liked Content</th>
                                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold">Date</th>
                                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-800 bg-gray-900">
                                        <template v-for="like in likes" :key="like.id">
                                            <tr v-if="like.likeable && like.user">
                                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium sm:pl-6">{{ like.user.name }}</td>
                                                <td class="px-3 py-4 text-sm text-gray-300 max-w-md truncate">
                                                    <span class="font-bold">{{ like.likeable_type.split('\\').pop() }}:</span>
                                                    {{ like.likeable.content }}
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-300">{{ new Date(like.created_at).toLocaleString() }}</td>
                                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                                    <button @click="deleteItem('like', like.id)" class="text-red-500 hover:text-red-700">Delete</button>
                                                </td>
                                            </tr>
                                        </template>
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
