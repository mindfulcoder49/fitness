<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GroupAdminPanel from '@/Components/Admin/GroupAdminPanel.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    group: Object,
});
</script>

<template>
    <Head :title="`Admin - ${group.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Admin: {{ group.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <GroupAdminPanel :group="group" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
