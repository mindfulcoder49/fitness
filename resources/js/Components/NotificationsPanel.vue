<script setup>
import { computed } from 'vue';

const props = defineProps({
    notifications: Object,
    lastChecked: String,
});

const emit = defineEmits(['close']);

const isNew = (item) => {
    if (!props.lastChecked) return true;
    return new Date(item.created_at) > new Date(props.lastChecked);
};

const allNotifications = computed(() => {
    const { posts, commentsOnUserPosts, likesOnUserContent } = props.notifications;

    const formattedPosts = posts.map(item => ({ ...item, type: 'New Post' }));
    const formattedComments = commentsOnUserPosts.map(item => ({ ...item, type: 'New Comment' }));
    const formattedLikes = likesOnUserContent.map(item => ({ ...item, type: 'New Like' }));

    return [...formattedPosts, ...formattedComments, ...formattedLikes]
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});
</script>

<template>
    <div class="fixed top-16 right-4 w-80 bg-gray-800 border border-gray-700 rounded-lg shadow-lg p-4 z-50 max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-white">Notifications</h3>
            <button @click="emit('close')" class="text-gray-400 hover:text-white">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div v-if="allNotifications.length > 0" class="space-y-3">
            <div v-for="item in allNotifications" :key="`${item.type}-${item.id}`" class="p-3 bg-gray-700 rounded-md text-sm">
                <div class="flex justify-between items-center">
                    <p class="font-bold text-indigo-400">{{ item.type }}</p>
                    <span v-if="isNew(item)" class="px-2 py-0.5 text-xs font-semibold text-white bg-green-500 rounded-full">New</span>
                </div>
                <div class="mt-1 text-gray-300">
                    <template v-if="item.type === 'New Post'">
                        <p><span class="font-semibold">{{ item.user.username }}</span> created a new post.</p>
                    </template>
                    <template v-if="item.type === 'New Comment'">
                        <p><span class="font-semibold">{{ item.user.username }}</span> commented on your post: <span class="italic">"{{ item.post.content.substring(0, 30) }}..."</span></p>
                    </template>
                    <template v-if="item.type === 'New Like'">
                        <p><span class="font-semibold">{{ item.user.username }}</span> liked your {{ item.likeable_type.split('\\').pop().toLowerCase() }}.</p>
                    </template>
                </div>
                <p class="text-xs text-gray-500 mt-2 text-right">{{ new Date(item.created_at).toLocaleString() }}</p>
            </div>
        </div>
        <div v-else>
            <p class="text-gray-400">No new notifications in the last week.</p>
        </div>
    </div>
</template>
