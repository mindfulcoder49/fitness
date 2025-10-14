<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    notifications: Object,
    lastChecked: String,
    groupId: Number,
});

const emit = defineEmits(['close']);

const isNew = (item) => {
    if (item.type === 'App Update') return true; // Changelog notifications are always new until read
    if (!props.lastChecked) return true;
    return new Date(item.created_at) > new Date(props.lastChecked);
};

const allNotifications = computed(() => {
    const { posts, commentsOnUserPosts, likesOnUserContent, changelogs } = props.notifications;

    const formattedPosts = (posts || []).map(item => ({ ...item, type: item.is_blog_post ? 'New Blog Post' : 'New Post' }));
    const formattedComments = (commentsOnUserPosts || []).map(item => ({ ...item, type: 'New Comment' }));
    const formattedLikes = (likesOnUserContent || []).map(item => ({ ...item, type: 'New Like' }));
    const formattedChangelogs = (changelogs || []).map(item => ({ ...item, type: 'App Update' }));

    return [...formattedPosts, ...formattedComments, ...formattedLikes, ...formattedChangelogs]
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

const getNotificationLink = (item) => {
    if (item.type === 'New Post' || item.type === 'New Blog Post') {
        const groupId = props.groupId || item.group_id;
        if (item.is_blog_post) {
            return route('groups.blog', { group: groupId, post: item.id });
        }
        return route('groups.show', { group: groupId, post: item.id });
    }
    if (item.type === 'New Comment') {
        const groupId = props.groupId || item.post.group_id;
        return route('groups.show', { group: groupId, post: item.post.id });
    }
    if (item.type === 'New Like' && item.likeable_type.endsWith('Post')) {
        const groupId = props.groupId || item.likeable.group_id;
        return route('groups.show', { group: groupId, post: item.likeable_id });
    }
    if (item.type === 'New Like' && item.likeable_type.endsWith('Comment')) {
        const groupId = props.groupId || item.likeable.post.group_id;
        return route('groups.show', { group: groupId, post: item.likeable.post_id });
    }
    if (item.type === 'App Update') {
        return route('changelog.index');
    }
    return '#';
};
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
            <Link v-for="item in allNotifications" :key="item.id" :href="getNotificationLink(item)" @click="emit('close')" class="block p-3 bg-gray-700 rounded-md text-sm hover:bg-gray-600">
                <div class="flex justify-between items-center">
                    <p class="font-bold text-indigo-400">{{ item.type }}</p>
                    <span v-if="isNew(item)" class="px-2 py-0.5 text-xs font-semibold text-white bg-green-500 rounded-full">New</span>
                </div>
                <div class="mt-1 text-gray-300">
                    <template v-if="item.type === 'New Post' || item.type === 'New Blog Post'">
                        <p><span class="font-semibold">{{ item.user.username }}</span> created a new {{ item.type === 'New Blog Post' ? 'blog post' : 'post' }}.</p>
                        <p v-if="item.content" class="mt-1 text-xs italic bg-gray-900/50 p-2 rounded">"{{ item.content.substring(0, 100) }}..."</p>
                    </template>
                    <template v-if="item.type === 'New Comment'">
                        <p><span class="font-semibold">{{ item.user.username }}</span> commented on your post: <span class="italic">"{{ item.post.content.substring(0, 30) }}..."</span></p>
                        <p v-if="item.content" class="mt-1 text-xs italic bg-gray-900/50 p-2 rounded">"{{ item.content.substring(0, 100) }}..."</p>
                    </template>
                    <template v-if="item.type === 'New Like'">
                        <p><span class="font-semibold">{{ item.user.username }}</span> liked your {{ item.likeable_type.split('\\').pop().toLowerCase() }}.</p>
                        <p v-if="item.likeable && item.likeable.content" class="mt-1 text-xs italic bg-gray-900/50 p-2 rounded">"{{ item.likeable.content.substring(0, 100) }}..."</p>
                    </template>
                    <template v-if="item.type === 'App Update'">
                        <p class="hover:underline">
                            {{ item.description }}
                        </p>
                    </template>
                </div>
                <p class="text-xs text-gray-500 mt-2 text-right">{{ new Date(item.created_at).toLocaleString() }}</p>
            </Link>
        </div>
        <div v-else>
            <p class="text-gray-400">No new notifications in the last week.</p>
        </div>
    </div>
</template>
