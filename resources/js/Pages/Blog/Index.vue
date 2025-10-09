<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Post from '@/Components/Post.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    posts: Array,
});
</script>

<template>
    <Head title="Blog" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Blog
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="space-y-6">
                    <Post v-for="post in posts" :key="post.id" :post="post" />
                    <div v-if="posts.length === 0" class="text-center text-gray-500 dark:text-gray-400">
                        There are no blog posts yet.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
