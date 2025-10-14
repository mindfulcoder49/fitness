<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    group: Object,
    initialMessages: Array,
});

const user = computed(() => usePage().props.auth.user);
const messages = ref([...props.initialMessages]);
const chatContainer = ref(null);
let pollingInterval = null;

const form = useForm({
    content: '',
});

const processing = ref(false);

const scrollToBottom = () => {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
        }
    });
};

const fetchNewMessages = async () => {
    const lastMessageId = messages.value.length > 0 ? messages.value[messages.value.length - 1].id : 0;
    try {
        const response = await axios.get(route('groups.messages.index', { group: props.group.id, after_id: lastMessageId }));
        if (response.data.length > 0) {
            const newMessages = response.data.reverse();
            messages.value.push(...newMessages);
            scrollToBottom();
        }
    } catch (error) {
        console.error('Error fetching new messages:', error);
    }
};

const submitMessage = async () => {
    if (form.content.trim() === '' || processing.value) return;

    processing.value = true;
    try {
        const response = await axios.post(route('groups.messages.store', { group: props.group.id }), {
            content: form.content,
        });
        messages.value.push(response.data);
        form.reset('content');
        scrollToBottom();
    } catch (error) {
        console.error('Error sending message:', error);
        // Optionally, handle validation errors from response
    } finally {
        processing.value = false;
    }
};

onMounted(() => {
    scrollToBottom();
    pollingInterval = setInterval(fetchNewMessages, 5000);
});

onUnmounted(() => {
    clearInterval(pollingInterval);
});
</script>

<template>
    <Head :title="`${group.name} Chat`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ group.name }} Chat
                </h2>
                <Link :href="route('groups.show', { group: group.id })" class="text-sm font-medium text-gray-300 hover:text-white">
                    Back to Group
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg flex flex-col" style="height: 70vh;">
                    <!-- Chat Messages -->
                    <div ref="chatContainer" class="flex-1 p-6 space-y-4 overflow-y-auto">
                        <div v-for="message in messages" :key="message.id" class="flex" :class="{ 'justify-end': message.user_id === user.id }">
                            <div class="flex items-end gap-2 max-w-lg">
                                <div v-if="message.user_id !== user.id" class="flex-shrink-0">
                                    <img class="h-8 w-8 rounded-full" :src="message.user.profile_photo_url" :alt="message.user.username">
                                </div>
                                <div class="rounded-lg px-4 py-2" :class="message.user_id === user.id ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-200'">
                                    <p class="text-sm font-semibold" v-if="message.user_id !== user.id">{{ message.user.username }}</p>
                                    <p>{{ message.content }}</p>
                                    <p class="text-xs opacity-70 mt-1 text-right">{{ new Date(message.created_at).toLocaleTimeString() }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Message Input -->
                    <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                        <form @submit.prevent="submitMessage">
                            <div class="flex items-center">
                                <textarea
                                    v-model="form.content"
                                    @keydown.enter.prevent="submitMessage"
                                    placeholder="Type a message..."
                                    class="w-full border-gray-300 bg-white text-gray-900 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-100 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
                                ></textarea>
                                <button type="submit" class="ml-4 px-4 py-2 bg-blue-600 text-white rounded-md" :disabled="processing">Send</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
