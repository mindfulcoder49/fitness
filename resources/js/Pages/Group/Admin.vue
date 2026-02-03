<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GroupAdminPanel from '@/Components/Admin/GroupAdminPanel.vue';
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();

const props = defineProps({
    group: Object,
    availabilitySummary: Array,
});
</script>

<template>
    <Head :title="`Admin - ${group.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-theme-text-primary">
                Admin: {{ group.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="page.props.flash?.success" class="mb-4 flex items-center justify-between bg-theme-success/20 border-l-4 border-theme-success text-theme-text-primary p-4 sm:rounded-lg" role="alert">
                    <p>{{ page.props.flash.success }}</p>
                    <button @click="page.props.flash.success = null" class="text-xl font-bold leading-none">&times;</button>
                </div>
                <div v-if="page.props.flash?.error" class="mb-4 flex items-center justify-between bg-theme-danger/20 border-l-4 border-theme-danger text-theme-text-primary p-4 sm:rounded-lg" role="alert">
                    <p>{{ page.props.flash.error }}</p>
                    <button @click="page.props.flash.error = null" class="text-xl font-bold leading-none">&times;</button>
                </div>
                <div class="overflow-hidden bg-theme-card shadow-sm sm:rounded-lg">
                    <GroupAdminPanel :group="group" :availability-summary="availabilitySummary" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
