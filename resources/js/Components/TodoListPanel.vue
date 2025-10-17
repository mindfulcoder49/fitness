<script setup>
import { Link, router } from '@inertiajs/vue3';

defineProps({
    todos: Array,
});

const emit = defineEmits(['close', 'refresh']);

const handleAction = (todo) => {
    if (todo.action_meta && todo.action_meta.method === 'post') {
        router.post(todo.action_meta.route, todo.action_meta.data || {}, {
            preserveScroll: true,
            onSuccess: () => {
                emit('refresh');
                // After the action is successful, navigate to the link.
                router.visit(todo.link, { preserveScroll: true });
            },
        });
    } else {
        // If there's no special action, just navigate.
        router.visit(todo.link);
    }
    emit('close');
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
            <li v-for="todo in todos" :key="todo.id">
                <Link :href="todo.link" @click.prevent="handleAction(todo)" class="block p-3 bg-gray-700/50 rounded-md hover:bg-gray-700/80 transition-colors duration-150">
                    <p class="text-sm font-semibold text-indigo-300">{{ todo.type }}</p>
                    <p class="text-gray-200 mt-1">{{ todo.description }}</p>
                    <p v-if="todo.details" class="mt-2 text-xs italic bg-gray-900/30 p-2 rounded">
                        {{ todo.details }}
                    </p>
                </Link>
            </li>
        </ul>
        <p v-else class="text-gray-400 text-center py-4">Your to-do list is empty. Great job!</p>
    </div>
</template>
