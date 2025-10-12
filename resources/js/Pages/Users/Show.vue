<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Post from '@/Components/Post.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    profileUser: Object,
    activityFeed: Array,
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString();
};
</script>

<template>
    <Head :title="profileUser.username" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ profileUser.username }}'s Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Profile Header -->
                <div class="bg-gray-800 shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-2xl font-bold text-white">{{ profileUser.username }}</h3>
                    <p v-if="profileUser.daily_fitness_goal" class="mt-2 text-indigo-400">
                        <span class="font-semibold">Goal:</span> {{ profileUser.daily_fitness_goal }}
                    </p>
                    <div class="mt-4 flex space-x-4 md:space-x-6 text-gray-400 text-sm md:text-base">
                        <span><span class="font-bold text-white">{{ profileUser.posts_count }}</span> Posts</span>
                        <span><span class="font-bold text-white">{{ profileUser.comments_count }}</span> Comments</span>
                        <span><span class="font-bold text-white">{{ profileUser.likes_count }}</span> Likes Given</span>
                        <span><span class="font-bold text-white">{{ profileUser.read_changelogs_count }}</span> Updates Read</span>
                    </div>
                </div>

                <!-- Activity Feed -->
                <div class="space-y-4">
                    <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Activity Feed</h3>
                    <div v-for="activity in activityFeed" :key="`${activity.activity_type}-${activity.id}`">
                        <!-- Post Activity -->
                        <Post v-if="activity.activity_type === 'post'" :post="activity" />

                        <!-- Other Activities -->
                        <div v-else class="bg-gray-800 shadow-sm sm:rounded-lg p-4">
                            <div class="text-sm text-gray-400 mb-2">{{ formatDate(activity.created_at) }}</div>
                            <div class="text-gray-300">
                                <!-- Comment Activity -->
                                <div v-if="activity.activity_type === 'comment' && activity.post">
                                    <Link :href="route('users.show', { user: profileUser.username })" class="font-bold text-white hover:underline">{{ profileUser.username }}</Link>
                                    commented on
                                    <Link :href="route('users.show', { user: activity.post.user.username })" class="font-bold text-white hover:underline">{{ activity.post.user.username }}'s</Link>
                                    post.
                                    <Link :href="route('dashboard', { post: activity.post.id })">
                                        <div class="mt-2 p-3 bg-gray-700 rounded-md hover:bg-gray-600">
                                            <p class="italic">"{{ activity.content }}"</p>
                                        </div>
                                    </Link>
                                </div>

                                <!-- Like Activity -->
                                <div v-if="activity.activity_type === 'like' && activity.likeable">
                                    <Link :href="route('users.show', { user: profileUser.username })" class="font-bold text-white hover:underline">{{ profileUser.username }}</Link>
                                    liked
                                    <Link v-if="activity.likeable.user" :href="route('users.show', { user: activity.likeable.user.username })" class="font-bold text-white hover:underline">{{ activity.likeable.user.username }}'s</Link>
                                    {{ activity.likeable_type.split('\\').pop().toLowerCase() }}.
                                    <Link v-if="activity.likeable_type.endsWith('Post')" :href="route('dashboard', { post: activity.likeable.id })">
                                        <div class="mt-2 p-3 bg-gray-700 rounded-md hover:bg-gray-600">
                                            <p class="italic truncate">"{{ activity.likeable.content }}"</p>
                                        </div>
                                    </Link>
                                    <div v-else class="mt-2 p-3 bg-gray-700 rounded-md">
                                         <p class="italic truncate">"{{ activity.likeable.content }}"</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="activityFeed.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8">
                        No activity to display.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
