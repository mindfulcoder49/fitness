<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    currentTasks: Array,
    latestMeetup: Object,
    latestChangelog: Object,
    latestGroupBlogPost: Object,
    latestGroupMessages: Array,
    groupId: Number,
});

const formatDateTime = (dateTime) => {
    if (!dateTime) return '';
    return new Date(dateTime).toLocaleString('en-US', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-US', {
        dateStyle: 'medium',
    });
};
</script>

<template>
    <div class="p-6 bg-gray-800 shadow-sm sm:rounded-lg space-y-6">
        <!-- Current Tasks -->
        <div v-if="currentTasks && currentTasks.length > 0">
            <h3 class="text-lg font-semibold text-indigo-400 mb-2">Today's Tasks</h3>
            <ul class="space-y-3">
                <li v-for="task in currentTasks" :key="task.id">
                    <h4 class="font-bold text-white">{{ task.title }}</h4>
                    <p class="mt-1 text-gray-300 text-sm">{{ task.description }}</p>
                </li>
            </ul>
            <p class="mt-4 text-xs text-gray-400">Select a task when you post an update to mark it as complete for the day.</p>
        </div>

        <!-- Latest Meetup -->
        <div v-if="latestMeetup">
            <h3 class="text-lg font-semibold text-indigo-400 mb-2">Next Meetup</h3>
            <Link :href="route('groups.meetups.index', { group: groupId })" class="block hover:bg-gray-700/50 p-3 rounded-md -m-3">
                <h4 class="font-bold text-white">{{ latestMeetup.title }}</h4>
                <p class="text-sm text-gray-400">{{ formatDateTime(latestMeetup.scheduled_at) }}</p>
                <p class="text-sm text-gray-300">{{ latestMeetup.location }}</p>
            </Link>
        </div>

        <!-- Latest Group Blog Post -->
        <div v-if="latestGroupBlogPost">
            <h3 class="text-lg font-semibold text-indigo-400 mb-2">Latest Blog Post</h3>
             <Link :href="route('groups.blog', { group: groupId, post: latestGroupBlogPost.id })" class="block hover:bg-gray-700/50 p-3 rounded-md -m-3">
                <h4 class="font-bold text-white truncate">{{ latestGroupBlogPost.title || 'Post' }}</h4>
                <p class="text-sm text-gray-400 truncate">{{ latestGroupBlogPost.content }}</p>
            </Link>
        </div>

        <!-- Latest Chat Messages -->
        <div v-if="latestGroupMessages && latestGroupMessages.length > 0">
            <h3 class="text-lg font-semibold text-indigo-400 mb-2">Latest Chat</h3>
            <Link :href="route('groups.chat', { group: groupId })" class="block hover:bg-gray-700/50 p-3 rounded-md -m-3 space-y-2">
                <div v-for="message in latestGroupMessages" :key="message.id">
                    <p class="text-sm text-gray-300 truncate"><span class="font-semibold text-white">{{ message.user.name }}:</span> {{ message.content }}</p>
                </div>
            </Link>
        </div>

        <!-- Latest Changelog -->
        <div v-if="latestChangelog">
            <h3 class="text-lg font-semibold text-indigo-400 mb-2">Latest Site Update</h3>
            <Link :href="route('changelog.index')" class="block hover:bg-gray-700/50 p-3 rounded-md -m-3">
                <h4 class="font-bold text-white">{{ latestChangelog.title }}</h4>
                <p class="text-sm text-gray-400">Released on {{ formatDate(latestChangelog.release_date) }}</p>
            </Link>
        </div>

        <!-- Points System -->
        <div>
            <h3 class="text-lg font-semibold text-indigo-400 mb-2">Points System</h3>
            <ul class="list-disc list-inside text-sm text-gray-400 space-y-1">
                <li><span class="font-semibold">Daily Task Post:</span> 3 points</li>
                <li><span class="font-semibold">Comment:</span> 1 point</li>
                <li><span class="font-semibold">Like Given:</span> 0.5 points</li>
                <li><span class="font-semibold">Like Received:</span> 1 point</li>
            </ul>
        </div>
    </div>
</template>
