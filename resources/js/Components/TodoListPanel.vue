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
    <div class="fixed top-16 right-4 z-40 w-full max-w-sm bg-theme-card rounded-lg shadow-lg p-4 text-theme-text-primary">
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-semibold text-lg">To-Do List</h3>
            <button @click="$emit('close')" class="text-theme-text-muted hover:text-theme-text-primary">&times;</button>
        </div>
        <ul v-if="todos.length > 0" class="space-y-2 max-h-96 overflow-y-auto">
            <li v-for="todo in todos" :key="todo.id">
                <Link :href="todo.link" @click.prevent="handleAction(todo)" class="block p-3 bg-theme-elevated/50 rounded-md hover:bg-theme-elevated/80 transition-colors duration-150">
                    <p class="text-sm font-semibold text-theme-accent-text">{{ todo.type }}</p>
                    <p class="text-theme-text-primary mt-1">{{ todo.description }}</p>
                    <p v-if="todo.details" class="mt-2 text-xs italic bg-theme-page/30 p-2 rounded">
                        {{ todo.details }}
                    </p>
                </Link>
            </li>
        </ul>
        <p v-else class="text-theme-text-muted text-center py-4">Your to-do list is empty. Great job!</p>
    </div>
</template>
