<script setup>
defineProps({
    metrics: Object,
});
</script>

<template>
    <div class="bg-gray-800 p-6 shadow-sm sm:rounded-lg">
        <h3 class="text-lg font-semibold text-indigo-400 mb-4">Your Stats</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-center">
            <div>
                <p class="text-2xl font-bold text-white">{{ metrics.days_posted }}</p>
                <p class="text-sm text-gray-400">Days Posted</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-white">{{ metrics.likes_on_posts }}</p>
                <p class="text-sm text-gray-400">Likes on Posts</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-white">{{ metrics.likes_given }}</p>
                <p class="text-sm text-gray-400">Likes Given</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-white">{{ metrics.total_comments }}</p>
                <p class="text-sm text-gray-400">Comments Made</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-white">{{ metrics.changelogs_read }}</p>
                <p class="text-sm text-gray-400">Updates Read</p>
            </div>
        </div>
    </div>
</template>
