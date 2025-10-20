<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    group: Object,
    meetups: Array,
});

const user = usePage().props.auth.user;

const upcomingMeetups = computed(() => props.meetups.filter(m => new Date(m.scheduled_at) >= new Date()));
const pastMeetups = computed(() => props.meetups.filter(m => new Date(m.scheduled_at) < new Date()));

const getUserRsvpStatus = (meetup) => {
    const rsvp = meetup.users.find(u => u.id === user.id);
    return rsvp ? rsvp.pivot.status : 'none';
};

const rsvp = (meetup, status) => {
    router.post(route('meetups.rsvp', { meetup: meetup.id }), { status }, {
        preserveScroll: true,
    });
};

const formatDateTime = (dateTime) => {
    return new Date(dateTime).toLocaleString('en-US', {
        dateStyle: 'full',
        timeStyle: 'short',
    });
};
</script>

<template>
    <Head :title="`${group.name} Meetups`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ group.name }} &mdash; Meetups
                </h2>
                <Link :href="route('groups.show', { group: group.id })" class="text-sm font-medium text-gray-300 hover:text-white">
                    &larr; Back to Group
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <div>
                    <h3 class="text-2xl font-bold text-white mb-4">Upcoming Meetups</h3>
                    <div v-if="upcomingMeetups.length > 0" class="space-y-6">
                        <div v-for="meetup in upcomingMeetups" :key="meetup.id" class="bg-gray-800 shadow-lg rounded-lg p-6">
                            <h4 class="text-xl font-bold text-indigo-400">{{ meetup.title }}</h4>
                            <p class="text-gray-400 text-sm mb-2">{{ formatDateTime(meetup.scheduled_at) }}</p>
                            <p class="text-gray-300 mb-1"><span class="font-semibold">Location:</span> {{ meetup.location }}</p>
                            <p v-if="meetup.type" class="text-gray-300 mb-4"><span class="font-semibold">Type:</span> {{ meetup.type }}</p>
                            <p class="text-gray-400 mb-4">{{ meetup.description }}</p>

                            <div class="flex items-center justify-between mt-4">
                                <div class="flex items-center space-x-2">
                                    <button @click="rsvp(meetup, 'attending')" :class="['px-3 py-1 text-sm rounded-md', getUserRsvpStatus(meetup) === 'attending' ? 'bg-green-600 text-white' : 'bg-gray-700 hover:bg-green-700']">Attending</button>
                                    <button @click="rsvp(meetup, 'interested')" :class="['px-3 py-1 text-sm rounded-md', getUserRsvpStatus(meetup) === 'interested' ? 'bg-yellow-600 text-white' : 'bg-gray-700 hover:bg-yellow-700']">Interested</button>
                                    <button @click="rsvp(meetup, 'not_attending')" class="px-3 py-1 text-sm rounded-md bg-gray-700 hover:bg-red-700">Can't Go</button>
                                </div>
                                <div class="text-sm text-gray-400">
                                    {{ meetup.users.filter(u => u.pivot.status === 'attending').length }} attending
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center text-gray-500 py-8">
                        <p>No upcoming meetups scheduled.</p>
                    </div>
                </div>

                <div>
                    <h3 class="text-2xl font-bold text-white mb-4">Past Meetups</h3>
                    <div v-if="pastMeetups.length > 0" class="space-y-4">
                         <div v-for="meetup in pastMeetups" :key="meetup.id" class="bg-gray-800/50 shadow rounded-lg p-4">
                            <h4 class="font-bold text-gray-400">{{ meetup.title }}</h4>
                            <p class="text-gray-500 text-sm">{{ formatDateTime(meetup.scheduled_at) }}</p>
                         </div>
                    </div>
                     <div v-else class="text-center text-gray-500 py-8">
                        <p>No past meetups.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
