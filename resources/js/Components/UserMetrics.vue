<script setup>
defineProps({
    metrics: Object,
});
</script>

<template>
    <div class="bg-gray-800 p-6 shadow-sm sm:rounded-lg">
        <h3 class="text-lg font-semibold text-indigo-400">Your Metrics</h3>
        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
            <div>
                <p class="text-2xl font-bold text-white">{{ metrics.days_posted }}</p>
                <p class="text-sm text-gray-400">Days Posted</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-white">{{ metrics.total_comments }}</p>
                <p class="text-sm text-gray-400">Comments Made</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-white">{{ metrics.likes_on_comments }}</p>
                <p class="text-sm text-gray-400">Likes on Comments</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-white">{{ metrics.avg_likes_per_comment }}</p>
                <p class="text-sm text-gray-400">Avg Likes/Comment</p>
            </div>
        </div>
    </div>
</template>
