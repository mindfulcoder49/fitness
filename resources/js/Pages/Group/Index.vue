<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MagazineLayout from '@/Layouts/MagazineLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

defineProps({
    groups: Array,
});

const joinGroup = (groupId) => {
    router.post(route('groups.join', groupId), {}, {
        preserveScroll: true,
    });
};

const leaveGroup = (group) => {
    if (confirm(`Are you sure you want to leave the group "${group.name}"?`)) {
        router.post(route('groups.leave', group.id), {}, {
            preserveScroll: true,
        });
    }
};

const layout = computed(() => user.value ? AuthenticatedLayout : MagazineLayout);
</script>

<template>
    <Head title="Public Groups" />

    <component :is="layout">
        <template #header v-if="user">
            <h2 class="font-semibold text-xl text-theme-text-primary leading-tight">
                Public Groups
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-if="page.props.flash?.error" class="mb-4 flex items-center justify-between bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
                    <p>{{ page.props.flash.error }}</p>
                    <button @click="page.props.flash.error = null" class="text-xl font-bold leading-none">&times;</button>
                </div>
                <div class="bg-theme-card overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-theme-text-primary">
                        <h3 class="text-2xl font-bold">Find a Community</h3>
                        <p class="mt-2 text-theme-text-muted">
                            Browse public groups and join one that fits your interests.
                        </p>
                        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="group in groups" :key="group.id" class="flex flex-col justify-between p-6 bg-theme-elevated rounded-lg transition">
                                <div>
                                    <Link :href="route('groups.preview', group.id)" class="text-xl font-semibold text-theme-text-primary hover:text-theme-accent transition">{{ group.name }}</Link>
                                    <p class="mt-2 text-theme-text-secondary h-24 overflow-y-auto">{{ group.description }}</p>
                                    <div v-if="group.min_members && !group.launched_at" class="mt-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-600 text-yellow-100">
                                        Gathering Members ({{ group.users_count }} / {{ group.min_members }})
                                    </div>
                                    <div class="mt-4 text-sm text-theme-text-muted">
                                        <p>Created by: {{ group.creator.name }}</p>
                                        <p>{{ group.users_count }} member(s)</p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <template v-if="user">
                                        <div v-if="group.is_member" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                            <Link :href="route('groups.show', group.id)" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-theme-elevated border border-transparent rounded-md font-semibold text-xs text-theme-text-primary uppercase tracking-widest hover:bg-theme-elevated focus:outline-none focus:ring-2 focus:ring-theme-accent-ring focus:ring-offset-2 transition ease-in-out duration-150">
                                                View
                                            </Link>
                                            <button @click="leaveGroup(group)" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-theme-danger border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-theme-danger-hover focus:outline-none focus:ring-2 focus:ring-theme-danger focus:ring-offset-2 transition ease-in-out duration-150">
                                                Leave
                                            </button>
                                        </div>
                                        <PrimaryButton v-else @click="joinGroup(group.id)" class="w-full justify-center">
                                            Join Group
                                        </PrimaryButton>
                                    </template>
                                    <Link v-else :href="route('login')" class="w-full inline-flex items-center justify-center px-4 py-2 bg-theme-accent border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-theme-accent-hover focus:outline-none focus:ring-2 focus:ring-theme-accent-ring focus:ring-offset-2 transition ease-in-out duration-150">
                                        Log in to Join
                                    </Link>
                                </div>
                            </div>
                        </div>
                         <div v-if="groups.length === 0" class="text-center py-12 text-theme-text-faint">
                            <p>There are no public groups available right now.</p>
                            <p v-if="user">Why not create one?</p>
                            <p v-else><Link :href="route('register')" class="text-theme-link hover:text-theme-link-hover">Register</Link> to create your own.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
