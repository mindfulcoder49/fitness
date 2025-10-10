<script setup>
import { Link } from '@inertiajs/vue3';

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
    if (todo.post_id) {
        return `#post-${todo.post_id}`;
    }
    if (todo.changelog_id) {
        return route('changelog.index');
    }
    return '#';
};

const isInertiaLink = (todo) => {
    return todo.id === 'set_goal' || !!todo.changelog_id;
};
</script>

<template>
    <div class="fixed top-16 right-4 w-80 bg-gray-800 border border-gray-700 rounded-lg shadow-lg p-4 z-50 max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-white">To-Do List</h3>
            <button @click="emit('close')" class="text-gray-400 hover:text-white">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div v-if="todos.length > 0" class="space-y-3">
            <div v-for="todo in todos" :key="todo.id" class="p-3 bg-gray-700 rounded-md text-sm">
                <div class="flex justify-between items-center">
                    <p class="font-bold text-amber-400">{{ todo.type }}</p>
                </div>
                <div class="mt-1 text-gray-300">
                    <p>{{ todo.description }}</p>
                </div>
                 <div class="text-right mt-2">
                    <Link v-if="isInertiaLink(todo)" :href="getActionLink(todo)" @click="emit('close')" class="text-sm text-indigo-400 hover:text-indigo-300">
                        Go &rarr;
                    </Link>
                    <a v-else :href="getActionLink(todo)" @click="emit('close')" class="text-sm text-indigo-400 hover:text-indigo-300">
                        Go &rarr;
                    </a>
                </div>
            </div>
        </div>
        <div v-else>
            <p class="text-gray-400">You're all caught up!</p>
        </div>
    </div>
</template>
