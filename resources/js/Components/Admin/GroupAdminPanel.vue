<script setup>
import { ref, computed } from 'vue';
import { router, Link, usePage, useForm } from '@inertiajs/vue3';

const props = defineProps({
    group: Object,
});

const user = usePage().props.auth.user;
const activeTab = ref('members');
const groupRoles = ['member', 'admin'];
const editingTask = ref(null);

const taskForm = useForm({
    title: '',
    description: '',
});

const allComments = computed(() => {
    return props.group.posts.flatMap(post => post.comments || []);
});

const allLikes = computed(() => {
    const postLikes = props.group.posts.flatMap(post => (post.likes || []).map(like => ({ ...like, on: 'Post' })));
    const commentLikes = allComments.value.flatMap(comment => (comment.likes || []).map(like => ({ ...like, on: 'Comment' })));
    return [...postLikes, ...commentLikes];
});

const updateGroupMemberRole = (user, newRole) => {
    router.patch(route('admin.groups.members.update-role', { group: props.group.id, user: user.id }), {
        role: newRole,
    }, { preserveScroll: true });
};

const deleteItem = (type, id) => {
    if (confirm(`Are you sure you want to delete this ${type}? This action cannot be undone.`)) {
        let routeName;
        let param;
        if (type === 'post') routeName = 'admin.posts.destroy', param = { post: id };
        else if (type === 'like') routeName = 'admin.likes.destroy', param = { like: id };
        else if (type === 'comment') routeName = 'admin.comments.destroy', param = { comment: id };
        
        if(routeName) {
            router.delete(route(routeName, param), { preserveScroll: true });
        }
    }
};

const updateBlogPostStatus = (post) => {
    router.patch(route('admin.posts.toggle-blog', { post: post.id }), {
        is_blog_post: post.is_blog_post,
    }, { preserveScroll: true });
};

const updateGroupPublicStatus = (group, event) => {
    router.patch(route('admin.groups.toggle-public', { group: group.id }), {
        is_public: event.target.checked,
    }, { preserveScroll: true });
};

const submitTask = () => {
    if (editingTask.value) {
        taskForm.patch(route('admin.groups.tasks.update', { task: editingTask.value.id }), {
            onSuccess: () => {
                editingTask.value = null;
                taskForm.reset();
            },
        });
    } else {
        taskForm.post(route('admin.groups.tasks.store', { group: props.group.id }), {
            onSuccess: () => taskForm.reset(),
        });
    }
};

const editTask = (task) => {
    editingTask.value = task;
    taskForm.title = task.title;
    taskForm.description = task.description;
};

const cancelEdit = () => {
    editingTask.value = null;
    taskForm.reset();
};

const deleteTask = (task) => {
    if (confirm('Are you sure you want to delete this task?')) {
        router.delete(route('admin.groups.tasks.destroy', { task: task.id }), { preserveScroll: true });
    }
};

const setCurrentTask = (task) => {
    router.patch(route('admin.groups.tasks.set-current', { task: task.id }), {}, { preserveScroll: true });
};
</script>

<template>
    <div class="p-4 border border-gray-700 rounded-lg">
        <div class="mb-4 border-b border-gray-600">
            <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                <button @click="activeTab = 'members'" :class="[activeTab === 'members' ? 'border-indigo-500 text-indigo-400' : 'border-transparent text-gray-400 hover:border-gray-500 hover:text-gray-300', 'whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium']">Members</button>
                <button @click="activeTab = 'posts'" :class="[activeTab === 'posts' ? 'border-indigo-500 text-indigo-400' : 'border-transparent text-gray-400 hover:border-gray-500 hover:text-gray-300', 'whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium']">Posts</button>
                <button @click="activeTab = 'comments'" :class="[activeTab === 'comments' ? 'border-indigo-500 text-indigo-400' : 'border-transparent text-gray-400 hover:border-gray-500 hover:text-gray-300', 'whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium']">Comments</button>
                <button @click="activeTab = 'likes'" :class="[activeTab === 'likes' ? 'border-indigo-500 text-indigo-400' : 'border-transparent text-gray-400 hover:border-gray-500 hover:text-gray-300', 'whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium']">Likes</button>
                <button @click="activeTab = 'tasks'" :class="[activeTab === 'tasks' ? 'border-indigo-500 text-indigo-400' : 'border-transparent text-gray-400 hover:border-gray-500 hover:text-gray-300', 'whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium']">Tasks</button>
                <button v-if="user.is_admin" @click="activeTab = 'settings'" :class="[activeTab === 'settings' ? 'border-indigo-500 text-indigo-400' : 'border-transparent text-gray-400 hover:border-gray-500 hover:text-gray-300', 'whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium']">Settings</button>
            </nav>
        </div>

        <!-- Members Table -->
        <div v-if="activeTab === 'members'">
            <table class="min-w-full divide-y divide-gray-600">
                <thead class="bg-gray-700/50">
                    <tr>
                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold sm:pl-6">Member</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold">Role</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold">Points</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <tr v-for="member in group.users" :key="member.id">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium sm:pl-6">
                            <Link :href="route('users.show', { user: member.username })" class="hover:underline">{{ member.name }}</Link>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <select :value="member.pivot.role" @change="updateGroupMemberRole(member, $event.target.value)" :disabled="member.id === group.creator_id" class="block w-full rounded-md border-gray-600 bg-gray-800 py-1.5 pl-3 pr-10 text-white focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm disabled:opacity-50">
                                <option v-for="role in groupRoles" :key="role" :value="role">{{ role }}</option>
                            </select>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-300">{{ member.pivot.points }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Posts Table -->
        <div v-if="activeTab === 'posts'">
            <table class="min-w-full divide-y divide-gray-600">
                <thead class="bg-gray-700/50">
                    <tr>
                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold sm:pl-6">Author</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold">Content</th>
                        <th class="px-3 py-3.5 text-center text-sm font-semibold">Is Blog</th>
                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <tr v-for="post in group.posts" :key="post.id">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium sm:pl-6">{{ post.user.name }}</td>
                        <td class="px-3 py-4 text-sm text-gray-300 max-w-md truncate">{{ post.content }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-center text-sm">
                            <input type="checkbox" v-model="post.is_blog_post" @change="updateBlogPostStatus(post)" class="h-4 w-4 rounded border-gray-600 bg-gray-800 text-indigo-600 focus:ring-indigo-600" />
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <button @click="deleteItem('post', post.id)" class="text-red-500 hover:text-red-700">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Comments Table -->
        <div v-if="activeTab === 'comments'">
            <table class="min-w-full divide-y divide-gray-600">
                <thead class="bg-gray-700/50">
                    <tr>
                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold sm:pl-6">Author</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold">Content</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold">Date</th>
                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <tr v-for="comment in allComments" :key="comment.id">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium sm:pl-6">{{ comment.user.name }}</td>
                        <td class="px-3 py-4 text-sm text-gray-300 max-w-md truncate">{{ comment.content }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-400">{{ new Date(comment.created_at).toLocaleDateString() }}</td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <button @click="deleteItem('comment', comment.id)" class="text-red-500 hover:text-red-700">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Likes Table -->
        <div v-if="activeTab === 'likes'">
            <table class="min-w-full divide-y divide-gray-600">
                <thead class="bg-gray-700/50">
                    <tr>
                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold sm:pl-6">User</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold">Liked Item Type</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold">Date</th>
                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <tr v-for="like in allLikes" :key="like.id">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium sm:pl-6">{{ like.user.name }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-300">{{ like.on }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-400">{{ new Date(like.created_at).toLocaleDateString() }}</td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <button @click="deleteItem('like', like.id)" class="text-red-500 hover:text-red-700">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Tasks Tab -->
        <div v-if="activeTab === 'tasks'">
            <h4 class="text-lg font-semibold mb-4">Manage Group Tasks</h4>
            <div class="bg-gray-900/50 p-4 rounded-lg mb-6">
                <form @submit.prevent="submitTask">
                    <h5 class="font-semibold mb-2">{{ editingTask ? 'Edit Task' : 'Create New Task' }}</h5>
                    <div class="space-y-3">
                        <input v-model="taskForm.title" placeholder="Task Title" class="w-full bg-gray-800 border-gray-700 rounded-md" required />
                        <textarea v-model="taskForm.description" placeholder="Task Description" class="w-full bg-gray-800 border-gray-700 rounded-md"></textarea>
                    </div>
                    <div class="mt-4 flex items-center space-x-2">
                        <button type="submit" class="px-3 py-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 rounded-md">{{ editingTask ? 'Update Task' : 'Create Task' }}</button>
                        <button v-if="editingTask" @click="cancelEdit" type="button" class="px-3 py-1.5 text-sm bg-gray-600 hover:bg-gray-700 rounded-md">Cancel</button>
                    </div>
                </form>
            </div>

            <h5 class="font-semibold mb-2">Existing Tasks</h5>
            <ul class="space-y-3">
                <li v-for="task in group.tasks" :key="task.id" class="p-3 bg-gray-900/50 rounded-lg flex justify-between items-center">
                    <div>
                        <p class="font-bold" :class="{'text-indigo-400': task.is_current}">{{ task.title }} <span v-if="task.is_current" class="text-xs font-normal">(Current)</span></p>
                        <p class="text-sm text-gray-400">{{ task.description }}</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button @click="setCurrentTask(task)" v-if="!task.is_current" class="text-xs text-green-400 hover:text-green-300">Set Current</button>
                        <button @click="editTask(task)" class="text-xs text-blue-400 hover:text-blue-300">Edit</button>
                        <button @click="deleteTask(task)" class="text-xs text-red-500 hover:text-red-700">Delete</button>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Settings Tab -->
        <div v-if="activeTab === 'settings' && user.is_admin">
            <h4 class="text-lg font-semibold mb-4">Group Settings</h4>
            <div class="flex items-center">
                <input type="checkbox" :checked="!!group.is_public" @change="updateGroupPublicStatus(group, $event)" :id="`public-toggle-${group.id}`" class="h-4 w-4 rounded border-gray-600 bg-gray-800 text-indigo-600 focus:ring-indigo-600" />
                <label :for="`public-toggle-${group.id}`" class="ml-2 text-sm font-medium">Public Group</label>
            </div>
            <p class="text-xs text-gray-500 mt-1">If checked, this group will be listed on the public groups page and anyone can join.</p>
        </div>
    </div>
</template>
