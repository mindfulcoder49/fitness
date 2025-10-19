<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    group: Object,
    isGroupAdmin: Boolean,
});

const dates = ref([]);
const userAvailability = ref([]);

const generateDates = () => {
    const dateArray = [];
    const today = new Date();
    for (let i = 0; i < 30; i++) {
        const date = new Date(today);
        date.setDate(today.getDate() + i);
        dateArray.push(date);
    }
    dates.value = dateArray;
};

const fetchData = async () => {
    try {
        const response = await axios.get(route('groups.availability.data', { group: props.group.id }));
        userAvailability.value = response.data.userAvailability || [];
    } catch (error) {
        console.error('Error fetching availability data:', error);
    }
};

const isAvailable = (date) => {
    return userAvailability.value.includes(date.toISOString().split('T')[0]);
};

const toggleAvailability = async (date) => {
    const dateString = date.toISOString().split('T')[0];
    const index = userAvailability.value.indexOf(dateString);

    if (index > -1) {
        userAvailability.value.splice(index, 1);
    } else {
        userAvailability.value.push(dateString);
    }

    try {
        await axios.post(route('groups.availability.update', { group: props.group.id }), {
            availability: userAvailability.value,
        });
    } catch (error) {
        console.error('Error updating availability:', error);
        // Revert change on error
        if (index > -1) {
            userAvailability.value.push(dateString);
        } else {
            userAvailability.value.splice(userAvailability.value.indexOf(dateString), 1);
        }
    }
};

onMounted(() => {
    generateDates();
    fetchData();
});

const formattedDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>

<template>
    <Head :title="`${group.name} Availability`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    <span v-if="group">{{ group.name }} &mdash; </span>Availability
                </h2>
                <Link :href="route('groups.show', { group: group.id })" class="text-sm font-medium text-gray-300 hover:text-white">
                    &larr; Back to Group
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <!-- My Availability View -->
                    <div>
                        <h3 class="text-lg font-medium text-white mb-4">Set Your Availability</h3>
                        <p class="text-sm text-gray-400 mb-6">Click on a date to mark it as a day you are available. Your changes are saved automatically.</p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                            <div v-for="date in dates" :key="date.toISOString()"
                                @click="toggleAvailability(date)"
                                :class="[
                                    'p-4 rounded-lg cursor-pointer transition-colors text-center',
                                    isAvailable(date) ? 'bg-green-500 text-white' : 'bg-gray-700 hover:bg-gray-600 text-gray-300'
                                ]">
                                <div class="font-bold text-lg">{{ date.toLocaleDateString('en-US', { day: 'numeric' }) }}</div>
                                <div class="text-sm">{{ date.toLocaleDateString('en-US', { month: 'short' }) }}</div>
                                <div class="text-xs">{{ date.toLocaleDateString('en-US', { weekday: 'short' }) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
