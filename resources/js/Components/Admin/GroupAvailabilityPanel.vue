<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    availabilitySummary: Array,
});

const formattedDate = (date) => {
    const d = new Date(date);
    // Adjust for timezone offset to show the correct date
    const userTimezoneOffset = d.getTimezoneOffset() * 60000;
    return new Date(d.getTime() + userTimezoneOffset).toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>

<template>
    <div>
        <h3 class="text-lg font-medium text-theme-text-primary mb-4">Group Availability Summary</h3>
        <div v-if="availabilitySummary && availabilitySummary.length > 0" class="space-y-4">
            <div v-for="item in availabilitySummary" :key="item.date" class="bg-theme-elevated p-4 rounded-lg">
                <div class="flex flex-col gap-2 sm:flex-row sm:justify-between sm:items-center">
                    <h4 class="font-bold text-theme-text-primary">{{ formattedDate(item.date) }}</h4>
                    <span class="px-3 py-1 text-sm font-semibold rounded-full w-fit" :class="item.count > 1 ? 'bg-theme-success text-white' : 'bg-theme-warning text-theme-text-primary'">
                        {{ item.count }} {{ item.count > 1 ? 'members' : 'member' }} available
                    </span>
                </div>
                <ul class="mt-3 list-disc list-inside text-theme-text-secondary">
                    <li v-for="member in item.users" :key="member.id">
                        <Link :href="route('users.show', member.username)" class="hover:underline">{{ member.name }}</Link>
                    </li>
                </ul>
            </div>
        </div>
        <div v-else class="text-center text-theme-text-muted py-8">
            <p>No availability has been set by any group members yet.</p>
        </div>
    </div>
</template>
