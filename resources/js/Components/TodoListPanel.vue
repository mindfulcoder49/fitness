<script setup>
import { Link, router } from '@inertiajs/vue3';

defineProps({
    todos: Array,
});

const emit = defineEmits(['close']);

const getActionLink = (todo) => {
    if (todo.id === 'set_goal') {
        return route('profile.edit');
    }
    if (todo.id === 'post_today') {
        return '#post-form'; // Assuming you have an element with id="post-form"
    }
    if (todo.post_id && todo.group_id) {
        if (todo.type === 'Like a Blog Post') {
            return route('groups.blog', { group: todo.group_id, post: todo.post_id });
        }
        return route('groups.show', { group: todo.group_id, post: todo.post_id });
    }
    if (todo.post_id) {
        return route('dashboard', { post: todo.post_id });
    }
    if (todo.changelog_id) {
        return route('changelog.index');
    }
    return '#';
};

const isAnchorTag = (todo) => {
    return todo.id === 'post_today';
};

const markChangelogAsRead = (changelogId) => {
    router.post(route('changelogs.mark-as-read', changelogId), {}, {
        preserveScroll: true,
    });
};

const likePost = (postId) => {
    router.post(route('posts.like', postId), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="fixed inset-0 z-30 bg-black/50" @click="$emit('close')"></div>
    <div class="fixed top-16 right-4 z-40 w-full max-w-sm bg-gray-800 rounded-lg shadow-lg p-4 text-white">
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-semibold text-lg">To-Do List</h3>
            <button @click="$emit('close')" class="text-gray-400 hover:text-white">&times;</button>
        </div>
        <ul v-if="todos.length > 0" class="space-y-2 max-h-96 overflow-y-auto">
            <li v-for="todo in todos" :key="todo.id" class="p-3 bg-gray-700/50 rounded-md flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-semibold text-indigo-300">{{ todo.type }}</p>
                    <Link v-if="todo.type === 'Daily Task'" :href="route('groups.show', todo.group_id)" class="text-gray-200 hover:underline">
                        {{ todo.description }}
                    </Link>
                    <p v-else class="text-gray-200">{{ todo.description }}</p>
                </div>
                <div class="ml-2">
                    <button v-if="todo.type === 'Read Update'" @click="markChangelogAsRead(todo.changelog_id)" class="px-2 py-1 text-xs bg-green-600 hover:bg-green-700 rounded">Done</button>
                    <Link v-if="todo.type === 'Like a Post' || todo.type === 'Like a Blog Post'" :href="route('groups.show', { group: todo.group_id, post: todo.post_id })" @click="likePost(todo.post_id)" class="px-2 py-1 text-xs bg-blue-600 hover:bg-blue-700 rounded">Like</Link>
                </div>
            </li>
        </ul>
        <p v-else class="text-gray-400 text-center py-4">Your to-do list is empty. Great job!</p>
    </div>
</template>
