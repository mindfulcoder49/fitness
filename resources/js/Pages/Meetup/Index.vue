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
                <h2 class="font-semibold text-xl text-theme-text-primary leading-tight">
                    {{ group.name }} &mdash; Meetups
                </h2>
                <Link :href="route('groups.show', { group: group.id })" class="text-sm font-medium text-theme-text-secondary hover:text-theme-text-primary">
                    &larr; Back to Group
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <div>
                    <h3 class="text-2xl font-bold text-theme-text-primary mb-4">Upcoming Meetups</h3>
                    <div v-if="upcomingMeetups.length > 0" class="space-y-6">
                        <div v-for="meetup in upcomingMeetups" :key="meetup.id" class="bg-theme-card shadow-lg rounded-lg p-6">
                            <h4 class="text-xl font-bold text-theme-accent-text">{{ meetup.title }}</h4>
                            <p class="text-theme-text-muted text-sm mb-2">{{ formatDateTime(meetup.scheduled_at) }}</p>
                            <p class="text-theme-text-secondary mb-1"><span class="font-semibold">Location:</span> {{ meetup.location }}</p>
                            <p v-if="meetup.type" class="text-theme-text-secondary mb-4"><span class="font-semibold">Type:</span> {{ meetup.type }}</p>
                            <p class="text-theme-text-muted mb-4">{{ meetup.description }}</p>

                            <div class="flex items-center justify-between mt-4">
                                <div class="flex items-center space-x-2">
                                    <button @click="rsvp(meetup, 'attending')" :class="['px-3 py-1 text-sm rounded-md', getUserRsvpStatus(meetup) === 'attending' ? 'bg-green-600 text-white' : 'bg-theme-elevated hover:bg-green-700']">Attending</button>
                                    <button @click="rsvp(meetup, 'interested')" :class="['px-3 py-1 text-sm rounded-md', getUserRsvpStatus(meetup) === 'interested' ? 'bg-yellow-600 text-white' : 'bg-theme-elevated hover:bg-yellow-700']">Interested</button>
                                    <button @click="rsvp(meetup, 'not_attending')" class="px-3 py-1 text-sm rounded-md bg-theme-elevated hover:bg-red-700">Can't Go</button>
                                </div>
                                <div class="text-sm text-theme-text-muted">
                                    {{ meetup.users.filter(u => u.pivot.status === 'attending').length }} attending
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center text-theme-text-faint py-8">
                        <p>No upcoming meetups scheduled.</p>
                    </div>
                </div>

                <div>
                    <h3 class="text-2xl font-bold text-theme-text-primary mb-4">Past Meetups</h3>
                    <div v-if="pastMeetups.length > 0" class="space-y-4">
                         <div v-for="meetup in pastMeetups" :key="meetup.id" class="bg-theme-card/50 shadow rounded-lg p-4">
                            <h4 class="font-bold text-theme-text-muted">{{ meetup.title }}</h4>
                            <p class="text-theme-text-faint text-sm">{{ formatDateTime(meetup.scheduled_at) }}</p>
                         </div>
                    </div>
                     <div v-else class="text-center text-theme-text-faint py-8">
                        <p>No past meetups.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
