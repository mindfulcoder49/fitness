<script setup>
defineProps({
    metrics: Object,
});
</script>

<template>
    <div class="bg-theme-card p-6 shadow-sm sm:rounded-lg">
        <h3 class="text-lg font-semibold text-theme-accent-text mb-4">Your Stats</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-center">
            <div>
                <p class="text-2xl font-bold text-theme-text-primary">{{ metrics.days_posted }}</p>
                <p class="text-sm text-theme-text-muted">Days Posted</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-theme-text-primary">{{ metrics.likes_on_posts }}</p>
                <p class="text-sm text-theme-text-muted">Likes on Posts</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-theme-text-primary">{{ metrics.likes_given }}</p>
                <p class="text-sm text-theme-text-muted">Likes Given</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-theme-text-primary">{{ metrics.total_comments }}</p>
                <p class="text-sm text-theme-text-muted">Comments Made</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-theme-text-primary">{{ metrics.changelogs_read }}</p>
                <p class="text-sm text-theme-text-muted">Updates Read</p>
            </div>
        </div>
    </div>
</template>
