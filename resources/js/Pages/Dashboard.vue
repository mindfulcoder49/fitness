<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import Post from '@/Components/Post.vue';
import UserMetrics from '@/Components/UserMetrics.vue';
import Leaderboard from '@/Components/Leaderboard.vue';
import ApplicationInvitation from '@/Components/ApplicationInvitation.vue';
import { computed } from 'vue';

const props = defineProps({
    posts: Array,
    prospectiveHasPostedToday: Boolean,
    userMetrics: Object,
    leaderboard: Array,
});

const user = computed(() => usePage().props.auth.user || {});

const form = useForm({
    content: '',
    image: null,
    video: null,
});

const goalForm = useForm({
    daily_fitness_goal: '',
});

const submitGoal = () => {
    goalForm.patch(route('profile.update-fitness-goal'), {
        onSuccess: () => goalForm.reset(),
    });
};

const submit = () => {
    form.post(route('posts.store'), {
        onSuccess: () => form.reset('content', 'image', 'video'),
    });
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex flex-col lg:grid lg:grid-cols-3 gap-6">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- User Metrics -->
                        <UserMetrics :metrics="userMetrics" />

                        <!-- Onboarding: Set Fitness Goal -->
                        <div v-if="!user.daily_fitness_goal" class="bg-indigo-900/50 p-6 shadow-sm sm:rounded-lg">
                            <h3 class="text-2xl font-bold text-white">Welcome, Prospective Member!</h3>
                            <p class="mt-2 text-gray-300">To begin your journey, please describe your daily fitness activity. This is your personal commitment.</p>
                            <form @submit.prevent="submitGoal" class="mt-4">
                                <textarea
                                    v-model="goalForm.daily_fitness_goal"
                                    placeholder="e.g., 'Run 2 miles every morning' or '30 minutes of yoga'"
                                    class="w-full rounded-md border-gray-600 bg-gray-900 text-gray-100 shadow-sm focus:border-indigo-600 focus:ring-indigo-600"
                                ></textarea>
                                <button type="submit" class="mt-2 rounded-md bg-indigo-500 px-4 py-2 text-white hover:bg-indigo-400" :disabled="goalForm.processing">Set My Goal</button>
                            </form>
                        </div>

                        <!-- Display Fitness Goal -->
                        <div v-if="user.daily_fitness_goal" class="bg-gray-800 p-6 shadow-sm sm:rounded-lg">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="text-lg font-semibold text-indigo-400">Your Daily Fitness Goal</h3>
                                    <p class="mt-2 text-xl text-white">{{ user.daily_fitness_goal }}</p>
                                </div>
                                <Link :href="route('profile.edit')" class="text-sm text-gray-400 hover:text-white">Edit Goal</Link>
                            </div>
                        </div>

                        <!-- Post Creation Form -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg dark:bg-gray-800">
                            <div class="p-6">
                                <div v-if="user.role === 'prospective' && prospectiveHasPostedToday" class="text-center text-gray-500 dark:text-gray-400">
                                    You have posted your update for today. Come back tomorrow!
                                </div>
                                <form v-else @submit.prevent="submit">
                                    <div v-if="user.role === 'prospective' && user.daily_fitness_goal">
                                        <p class="text-gray-900 dark:text-gray-100">
                                            I completed my daily fitness goal: {{ user.daily_fitness_goal }} and...
                                        </p>
                                    </div>
                                    <textarea
                                        v-model="form.content"
                                        :placeholder="user.role === 'prospective' && user.daily_fitness_goal ? '...add more details about your activity.' : 'Post an update on your fitness activity...'"
                                        class="w-full border-gray-300 bg-white text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-100 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
                                        :disabled="user.role === 'prospective' && prospectiveHasPostedToday"
                                    ></textarea>
                                    <div class="mt-4 flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <div v-if="user.can_post_images">
                                                <label for="image-upload" class="cursor-pointer text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                                    Add Image
                                                </label>
                                                <input id="image-upload" type="file" class="hidden" @input="form.image = $event.target.files[0]" accept="image/*" />
                                            </div>
                                             <div v-if="user.can_post_videos">
                                                <label for="video-upload" class="cursor-pointer text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                                    Add Video
                                                </label>
                                                <input id="video-upload" type="file" class="hidden" @input="form.video = $event.target.files[0]" accept="video/*" />
                                            </div>
                                        </div>
                                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md dark:bg-gray-200 dark:text-gray-800" :disabled="form.processing || (user.role === 'prospective' && prospectiveHasPostedToday)">Post</button>
                                    </div>
                                     <div v-if="form.progress" class="mt-2 w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                        <div class="bg-blue-600 h-2.5 rounded-full" :style="{ width: form.progress.percentage + '%' }"></div>
                                    </div>
                                    <div v-if="form.image" class="mt-2 text-sm text-gray-500">Image selected: {{ form.image.name }}</div>
                                    <div v-if="form.video" class="mt-2 text-sm text-gray-500">Video selected: {{ form.video.name }}</div>
                                    <p v-if="form.errors.daily_limit" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.daily_limit }}</p>
                                </form>
                            </div>
                        </div>

                        <!-- Posts Feed -->
                        <div class="space-y-6">
                            <Post v-for="post in posts" :key="post.id" :post="post" />
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="lg:col-span-1 order-first lg:order-none space-y-6">
                        <Leaderboard :users="leaderboard" />
                        <ApplicationInvitation />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
