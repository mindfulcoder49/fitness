<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import marked from 'marked';

const props = defineProps({
    post: Object,
});

const user = usePage().props.auth.user;

const parsedContent = computed(() => {
    if (!props.post.content) return '';
    return marked(props.post.content, { breaks: true });
});

const commentForm = useForm({
    content: '',
});

const showComments = ref(false);

const submitComment = () => {
    commentForm.post(route('comments.store', props.post.id), {
        preserveScroll: true,
        onSuccess: () => commentForm.reset(),
    });
};

const toggleLike = (likeableId, likeableType) => {
    router.post(route('likes.store'), {
        likeable_id: likeableId,
        likeable_type: likeableType,
    }, {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="p-6 bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4 dark:bg-gray-800">
        <div class="flex justify-between">
            <div class="flex items-center">
                <Link :href="route('users.show', { user: post.user.username })" class="font-bold text-gray-800 dark:text-gray-200 hover:underline">{{ post.user.username }}</Link>
                <div class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ new Date(post.created_at).toLocaleString() }}
                </div>
            </div>
            <Dropdown v-if="post.can.delete" align="right" width="48">
                <template #trigger>
                    <button>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 dark:text-gray-300" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                        </svg>
                    </button>
                </template>
                <template #content>
                    <DropdownLink as="button" :href="route('posts.destroy', post.id)" method="delete">
                        Delete
                    </DropdownLink>
                </template>
            </Dropdown>
        </div>
        <div v-if="post.content" class="mt-4 text-lg text-gray-900 dark:text-gray-100 prose dark:prose-invert max-w-none" v-html="parsedContent"></div>

        <div v-if="post.image_url" class="mt-4">
            <img :src="post.image_url" alt="Post image" class="max-w-full rounded-lg" />
        </div>

        <div v-if="post.video_url" class="mt-4">
            <video :src="post.video_url" controls class="max-w-full rounded-lg"></video>
        </div>

        <div class="mt-4 flex items-center space-x-4">
            <button @click="toggleLike(post.id, 'App\\Models\\Post')" class="flex items-center text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-500 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" :class="post.is_liked ? 'text-red-500' : ''" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                </svg>
                <span>{{ post.likes_count }}</span>
            </button>
            <button @click="showComments = !showComments" class="flex items-center text-gray-500 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.839 8.839 0 01-4.083-.985L2 17l.93-2.685A8.973 8.973 0 012 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM4.416 14.584A6.973 6.973 0 0010 15c3.314 0 6-2.686 6-6s-2.686-6-6-6-6 2.686-6 6c0 1.31.378 2.522 1.034 3.536l-1.12 3.214 3.502-1.166z" clip-rule="evenodd" />
                </svg>
                <span>{{ post.comments_count }}</span>
            </button>
        </div>
        <div v-if="showComments" class="mt-4">
            <div v-for="comment in post.comments" :key="comment.id" class="mt-2 bg-gray-100 p-2 rounded-lg dark:bg-gray-700">
                <div class="flex justify-between items-start">
                    <div>
                        <p><Link :href="route('users.show', { user: comment.user.username })" class="font-bold text-gray-800 dark:text-gray-200 hover:underline">{{ comment.user.username }}</Link>: <span class="text-gray-700 dark:text-gray-300">{{ comment.content }}</span></p>
                        <div class="flex items-center space-x-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ new Date(comment.created_at).toLocaleString() }}</p>
                            <button @click="toggleLike(comment.id, 'App\\Models\\Comment')" class="flex items-center text-xs text-gray-500 dark:text-gray-400 hover:text-red-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" :class="comment.is_liked ? 'text-red-500' : ''" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ comment.likes.length }}</span>
                            </button>
                        </div>
                    </div>
                    <Dropdown v-if="comment.can.delete" align="right" width="48">
                        <template #trigger>
                            <button class="p-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 dark:text-gray-300" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                </svg>
                            </button>
                        </template>
                        <template #content>
                            <DropdownLink as="button" :href="route('comments.destroy', comment.id)" method="delete" preserve-scroll>
                                Delete
                            </DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </div>
            <form @submit.prevent="submitComment" class="mt-4">
                <textarea v-model="commentForm.content" class="w-full border-gray-300 bg-white text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-100 dark:focus:border-indigo-600 dark:focus:ring-indigo-600" placeholder="Add a comment..."></textarea>
                <button type="submit" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 transition-colors">Comment</button>
            </form>
        </div>
    </div>
</template>

<style scoped>
.prose img {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
}
.prose video {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
}
/* style paragraph margins */
.prose :deep(*) {
    margin-top: 1.25em;
    margin-bottom: 1.25em;
}
</style>