<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Post from '@/Components/Post.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    posts: Array,
    featuredPost: Object,
    group: Object, // Optional group prop
});
</script>

<template>
    <Head :title="group ? `${group.name} Blog` : 'Blog'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    <span v-if="group">{{ group.name }} &mdash; </span>Blog
                </h2>
                <Link v-if="group" :href="route('groups.show', { group: group.id })" class="text-sm font-medium text-gray-300 hover:text-white">
                    &larr; Back to Group
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Featured Post -->
                <div v-if="featuredPost" class="bg-white overflow-hidden shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-indigo-400">Featured Post</h3>
                    </div>
                    <Post :post="featuredPost" />
                </div>

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
