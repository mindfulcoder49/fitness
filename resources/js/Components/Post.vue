<script setup>
import { ref, computed, watch } from 'vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import marked from 'marked';
import MarkdownEditor from '@/Components/MarkdownEditor.vue';

const props = defineProps({
    post: Object,
});

const user = usePage().props.auth.user;
const isEditing = ref(false);

const editForm = useForm({
    content: props.post.content,
});

// Watch for prop changes to keep the form in sync
watch(() => props.post.content, (newContent) => {
    editForm.content = newContent;
});

const parsedContent = computed(() => {
    if (!props.post.content) return '';
    return marked(props.post.content, { breaks: true });
});

// Logic for collapsing long posts
const CONTENT_COLLAPSE_THRESHOLD = 600; // characters
const isLongPost = computed(() => props.post.content && props.post.content.length > CONTENT_COLLAPSE_THRESHOLD);
const isExpanded = ref(false);

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

const updatePost = () => {
    editForm.patch(route('posts.update', props.post.id), {
        preserveScroll: true,
        onSuccess: () => {
            isEditing.value = false;
        },
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
    <div class="p-6 bg-theme-card overflow-hidden shadow-sm sm:rounded-lg mb-4">
        <div class="flex justify-between">
            <div class="flex items-center flex-wrap">
                <Link :href="route('users.show', { user: post.user.username })" class="font-bold text-theme-text-primary hover:underline">{{ post.user.username }}</Link>
                <span v-if="post.group" class="text-theme-text-faint mx-2">&middot;</span>
                <Link v-if="post.group" :href="route('groups.show', { group: post.group.id })" class="text-sm text-theme-accent-text hover:underline">{{ post.group.name }}</Link>
                <div v-if="post.group_task" class="ml-2 text-xs bg-theme-elevated text-theme-text-secondary px-2 py-0.5 rounded-full">
                    Task: {{ post.group_task.title }}
                </div>
                <div class="ml-2 text-sm text-theme-text-muted">
                    {{ new Date(post.created_at).toLocaleString() }}
                </div>
            </div>
            <Dropdown v-if="post.can.update || post.can.delete" align="right" width="48">
                <template #trigger>
                    <button>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-theme-text-secondary" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                        </svg>
                    </button>
                </template>
                <template #content>
                    <button v-if="post.can.update" @click="isEditing = true" class="block w-full px-4 py-2 text-left text-sm leading-5 text-theme-text-secondary hover:bg-theme-card focus:outline-none focus:bg-theme-card transition duration-150 ease-in-out">
                        Edit
                    </button>
                    <DropdownLink v-if="post.can.delete" as="button" :href="route('posts.destroy', post.id)" method="delete">
                        Delete
                    </DropdownLink>
                </template>
            </Dropdown>
        </div>

        <!-- View Mode -->
        <template v-if="!isEditing">
            <div v-if="post.content"
                 class="mt-4 text-lg text-theme-text-primary prose dark:prose-invert max-w-none relative post-content"
                 :class="{ 'max-h-60 overflow-hidden': isLongPost && !isExpanded }">
                <div v-html="parsedContent"></div>
                <div v-if="isLongPost && !isExpanded" class="absolute bottom-0 left-0 w-full h-24 bg-gradient-to-t from-theme-card to-transparent pointer-events-none"></div>
            </div>

            <div v-if="isLongPost" class="mt-2">
                <button @click="isExpanded = !isExpanded" class="text-sm font-semibold text-theme-accent-text hover:text-theme-accent-text">
                    {{ isExpanded ? 'Show less' : 'Show more' }}
                </button>
            </div>

            <div v-if="post.image_url" class="mt-4">
                <img :src="post.image_url" alt="Post image" class="max-w-full rounded-lg" />
            </div>

            <div v-if="post.video_url" class="mt-4">
                <video :src="post.video_url" controls class="max-w-full rounded-lg"></video>
            </div>
        </template>

        <!-- Edit Mode -->
        <template v-else>
            <div class="mt-4">
                <MarkdownEditor v-model="editForm.content" />
                <div v-if="editForm.errors.content" class="mt-2 text-sm text-theme-danger">{{ editForm.errors.content }}</div>
                <div class="mt-4 flex justify-end space-x-2">
                    <button @click="isEditing = false; editForm.reset();" class="px-4 py-2 text-sm font-medium text-theme-text-secondary bg-theme-border rounded-md hover:bg-theme-border">
                        Cancel
                    </button>
                    <button @click="updatePost" :disabled="editForm.processing" class="px-4 py-2 text-sm font-medium text-theme-text-primary bg-theme-accent rounded-md hover:bg-theme-accent-hover disabled:opacity-50">
                        Save Changes
                    </button>
                </div>
            </div>
        </template>

        <div v-if="!isEditing" class="mt-4 flex items-center space-x-4">
            <button @click="toggleLike(post.id, 'App\\Models\\Post')" class="flex items-center text-theme-text-muted hover:text-theme-danger transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" :class="post.is_liked ? 'text-theme-danger' : ''" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                </svg>
                <span>{{ post.likes_count }}</span>
            </button>
            <button @click="showComments = !showComments" class="flex items-center text-theme-text-muted hover:text-theme-link transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.839 8.839 0 01-4.083-.985L2 17l.93-2.685A8.973 8.973 0 012 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM4.416 14.584A6.973 6.973 0 0010 15c3.314 0 6-2.686 6-6s-2.686-6-6-6-6 2.686-6 6c0 1.31.378 2.522 1.034 3.536l-1.12 3.214 3.502-1.166z" clip-rule="evenodd" />
                </svg>
                <span>{{ post.comments_count }}</span>
            </button>
        </div>
        <div v-if="showComments && !isEditing" class="mt-4">
            <div v-for="comment in post.comments" :key="comment.id" class="mt-2 bg-theme-elevated p-2 rounded-lg">
                <div class="flex justify-between items-start">
                    <div>
                        <p><Link :href="route('users.show', { user: comment.user.username })" class="font-bold text-theme-text-primary hover:underline">{{ comment.user.username }}</Link>: <span class="text-theme-text-secondary">{{ comment.content }}</span></p>
                        <div class="flex items-center space-x-2">
                            <p class="text-xs text-theme-text-faint">{{ new Date(comment.created_at).toLocaleString() }}</p>
                            <button @click="toggleLike(comment.id, 'App\\Models\\Comment')" class="flex items-center text-xs text-theme-text-muted hover:text-theme-danger">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" :class="comment.is_liked ? 'text-theme-danger' : ''" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                                </svg>
                                <span>{{ comment.likes.length }}</span>
                            </button>
                        </div>
                    </div>
                    <Dropdown v-if="comment.can.delete" align="right" width="48">
                        <template #trigger>
                            <button class="p-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-theme-text-secondary" viewBox="0 0 20 20" fill="currentColor">
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
                <textarea v-model="commentForm.content" class="w-full border-theme-border bg-theme-input text-theme-text-primary focus:border-theme-accent focus:ring-theme-accent-ring rounded-md shadow-sm" placeholder="Add a comment..."></textarea>
                <button type="submit" class="mt-2 px-4 py-2 bg-theme-accent text-white rounded-md hover:bg-theme-accent-hover transition-colors">Comment</button>
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

.post-content :deep(> :last-child) {
    margin-bottom: 0;
}
</style>