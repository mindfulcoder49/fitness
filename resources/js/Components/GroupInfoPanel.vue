<script setup>
import { onMounted } from 'vue';

const props = defineProps({
    currentTasks: Array,
});

onMounted(() => {
    console.log('GroupInfoPanel.vue: Received currentTasks prop:', props.currentTasks);
});
</script>

<template>
    <div class="p-6 bg-gray-800 shadow-sm sm:rounded-lg">
        <div v-if="currentTasks && currentTasks.length > 0">
            <h3 class="text-lg font-semibold text-indigo-400 mb-2">Today's Tasks</h3>
            <ul class="space-y-3">
                <li v-for="task in currentTasks" :key="task.id">
                    <h4 class="font-bold text-white text-lg">{{ task.title }}</h4>
                    <p class="mt-1 text-gray-300 text-sm">{{ task.description }}</p>
                </li>
            </ul>
            <p class="mt-4 text-xs text-gray-400">Select a task when you post an update to mark it as complete for the day.</p>
        </div>
        <div v-else>
            <h3 class="text-lg font-semibold text-indigo-400 mb-2">Group Info</h3>
            <p class="text-gray-300">There are no active tasks for this group today.</p>
            <p class="mt-2 text-sm text-gray-400">You can still post general updates to the group feed.</p>
        </div>
        <hr class="border-gray-700 my-4" />
        <div>
            <h4 class="font-semibold text-white mb-2">Points System</h4>
            <ul class="list-disc list-inside text-sm text-gray-400 space-y-1">
                <li><span class="font-semibold">Daily Task Post:</span> 3 points</li>
                <li><span class="font-semibold">Comment:</span> 1 point</li>
                <li><span class="font-semibold">Like Given:</span> 0.5 points</li>
                <li><span class="font-semibold">Like Received:</span> 1 point</li>
            </ul>
        </div>
    </div>
</template>
