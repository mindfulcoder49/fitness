<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    group: Object,
});

const editingMeetup = ref(null);

const form = useForm({
    title: '',
    description: '',
    type: '',
    location: '',
    latitude: null,
    longitude: null,
    scheduled_at: '',
});

const submitMeetup = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            cancelEdit();
            router.reload({ only: ['group'] });
        },
        onError: () => {
            // Errors will be displayed automatically by the form helper
        },
    };

    if (editingMeetup.value) {
        form.patch(route('admin.meetups.update', { meetup: editingMeetup.value.id }), options);
    } else {
        form.post(route('admin.groups.meetups.store', { group: props.group.id }), {
            ...options,
            onSuccess: () => {
                form.reset();
                router.reload({ only: ['group'] });
            }
        });
    }
};

const editMeetup = (meetup) => {
    editingMeetup.value = meetup;
    form.title = meetup.title;
    form.description = meetup.description;
    form.type = meetup.type;
    form.location = meetup.location;
    form.latitude = meetup.latitude;
    form.longitude = meetup.longitude;
    form.scheduled_at = meetup.scheduled_at.slice(0, 16); // Format for datetime-local input
};

const cancelEdit = () => {
    editingMeetup.value = null;
    form.reset();
};

const deleteMeetup = (meetup) => {
    if (confirm('Are you sure you want to delete this meetup?')) {
        router.delete(route('admin.meetups.destroy', { meetup: meetup.id }), {
            preserveScroll: true,
            onSuccess: () => router.reload({ only: ['group'] }),
        });
    }
};
</script>

<template>
    <div>
        <h4 class="text-lg font-semibold mb-4">Manage Group Meetups</h4>
        <div class="bg-gray-900/50 p-4 rounded-lg mb-6">
            <form @submit.prevent="submitMeetup">
                <h5 class="font-semibold mb-2">{{ editingMeetup ? 'Edit Meetup' : 'Create New Meetup' }}</h5>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <input v-model="form.title" placeholder="Meetup Title" class="w-full bg-gray-800 border-gray-700 rounded-md" required />
                        <p v-if="form.errors.title" class="mt-1 text-sm text-red-400">{{ form.errors.title }}</p>
                    </div>
                    <div>
                        <input v-model="form.type" placeholder="Meetup Type (e.g., Run, Gym)" class="w-full bg-gray-800 border-gray-700 rounded-md" />
                        <p v-if="form.errors.type" class="mt-1 text-sm text-red-400">{{ form.errors.type }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <textarea v-model="form.description" placeholder="Description" class="w-full bg-gray-800 border-gray-700 rounded-md"></textarea>
                        <p v-if="form.errors.description" class="mt-1 text-sm text-red-400">{{ form.errors.description }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <input v-model="form.location" placeholder="Location (e.g., Central Park)" class="w-full bg-gray-800 border-gray-700 rounded-md" required />
                        <p v-if="form.errors.location" class="mt-1 text-sm text-red-400">{{ form.errors.location }}</p>
                    </div>
                    <div>
                        <input v-model="form.latitude" type="number" step="any" placeholder="Latitude" class="w-full bg-gray-800 border-gray-700 rounded-md" />
                        <p v-if="form.errors.latitude" class="mt-1 text-sm text-red-400">{{ form.errors.latitude }}</p>
                    </div>
                    <div>
                        <input v-model="form.longitude" type="number" step="any" placeholder="Longitude" class="w-full bg-gray-800 border-gray-700 rounded-md" />
                        <p v-if="form.errors.longitude" class="mt-1 text-sm text-red-400">{{ form.errors.longitude }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <input v-model="form.scheduled_at" type="datetime-local" class="w-full bg-gray-800 border-gray-700 rounded-md" required />
                        <p v-if="form.errors.scheduled_at" class="mt-1 text-sm text-red-400">{{ form.errors.scheduled_at }}</p>
                    </div>
                </div>
                <div class="mt-4 flex items-center space-x-2">
                    <button type="submit" :disabled="form.processing" class="px-3 py-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 rounded-md disabled:opacity-50">{{ editingMeetup ? 'Update Meetup' : 'Create Meetup' }}</button>
                    <button v-if="editingMeetup" @click="cancelEdit" type="button" class="px-3 py-1.5 text-sm bg-gray-600 hover:bg-gray-700 rounded-md">Cancel</button>
                </div>
            </form>
        </div>

        <h5 class="font-semibold mb-2">Existing Meetups</h5>
        <ul class="space-y-3">
            <li v-for="meetup in group.meetups" :key="meetup.id" class="p-3 bg-gray-900/50 rounded-lg flex justify-between items-start">
                <div>
                    <p class="font-bold">{{ meetup.title }}</p>
                    <p class="text-sm text-gray-400">{{ meetup.location }} - {{ new Date(meetup.scheduled_at).toLocaleString() }}</p>
                </div>
                <div class="flex items-center space-x-2 flex-shrink-0">
                    <button @click="editMeetup(meetup)" class="text-xs text-blue-400 hover:text-blue-300">Edit</button>
                    <button @click="deleteMeetup(meetup)" class="text-xs text-red-500 hover:text-red-700">Delete</button>
                </div>
            </li>
             <li v-if="!group.meetups || group.meetups.length === 0">
                <p class="text-gray-400">No meetups have been created yet.</p>
            </li>
        </ul>
    </div>
</template>
