<script setup>
import { computed } from 'vue';

const props = defineProps({
    group: Object,
});

const likeStats = computed(() => {
    const stats = {};
    const users = props.group.users;

    // Initialize stats object
    users.forEach(liker => {
        stats[liker.id] = {
            name: liker.name,
            likesGiven: {},
        };
        users.forEach(liked => {
            stats[liker.id].likesGiven[liked.id] = 0;
        });
    });

    // Process post likes
    props.group.posts.forEach(post => {
        const likedUserId = post.user_id;
        if (post.likes) {
            post.likes.forEach(like => {
                const likerId = like.user_id;
                if (stats[likerId] && stats[likerId].likesGiven.hasOwnProperty(likedUserId)) {
                    stats[likerId].likesGiven[likedUserId]++;
                }
            });
        }

        // Process comment likes
        if (post.comments) {
            post.comments.forEach(comment => {
                const commentLikedUserId = comment.user_id;
                if (comment.likes) {
                    comment.likes.forEach(like => {
                        const likerId = like.user_id;
                        if (stats[likerId] && stats[likerId].likesGiven.hasOwnProperty(commentLikedUserId)) {
                            stats[likerId].likesGiven[commentLikedUserId]++;
                        }
                    });
                }
            });
        }
    });

    return {
        users: users.map(u => ({ id: u.id, name: u.name })),
        stats: Object.entries(stats).map(([likerId, data]) => ({
            likerId: parseInt(likerId),
            likerName: data.name,
            likes: data.likesGiven,
        })),
    };
});
</script>

<template>
    <div class="overflow-x-auto">
        <h4 class="text-lg font-semibold mb-4">User Like Statistics</h4>
        <p class="text-sm text-gray-400 mb-4">This table shows how many times a user (row) has liked posts or comments from another user (column) within this group.</p>
        <table class="min-w-full divide-y divide-gray-600 border border-gray-600">
            <thead class="bg-gray-700/50">
                <tr>
                    <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold sm:pl-6 sticky left-0 bg-gray-700/50 z-10">Liker</th>
                    <th v-for="user in likeStats.users" :key="user.id" class="px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap">
                        {{ user.name }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                <tr v-for="row in likeStats.stats" :key="row.likerId">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium sm:pl-6 sticky left-0 bg-gray-800 z-10">{{ row.likerName }}</td>
                    <td v-for="user in likeStats.users" :key="user.id" class="whitespace-nowrap px-3 py-4 text-sm text-center" :class="{'text-gray-400': row.likes[user.id] === 0, 'font-bold': row.likes[user.id] > 0}">
                        {{ row.likes[user.id] }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
