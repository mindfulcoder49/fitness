<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import GroupAdminPanel from '@/Components/Admin/GroupAdminPanel.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    users: Array,
    groups: Array,
    groupsEnabled: Boolean,
});

const settingsForm = useForm({
    groups_enabled: props.groupsEnabled,
});

const currentLogoUrl = computed(() => usePage().props.logoUrl);
const logoForm = useForm({ logo: null });
const logoPreview = ref(null);

function onLogoChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    logoForm.logo = file;
    logoPreview.value = URL.createObjectURL(file);
}

function submitLogo() {
    logoForm.post(route('admin.settings.logo'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { logoPreview.value = null; logoForm.reset(); },
    });
}

const saveSettings = () => {
    settingsForm.patch(route('admin.settings.update'), { preserveScroll: true });
};

const activeTab = ref('groups');
const openGroup = ref(null);

const toggleGroup = (groupId) => {
    openGroup.value = openGroup.value === groupId ? null : groupId;
};

const updateMediaPermissions = (user) => {
    router.patch(route('admin.users.update-media-permissions', { user: user.id }), {
        can_post_images: user.can_post_images,
        can_post_videos: user.can_post_videos,
    }, { preserveScroll: true });
};

const deleteUser = (user) => {
    if (confirm(`Are you sure you want to delete ${user.name}? This action cannot be undone.`)) {
        router.delete(route('admin.users.destroy', { user: user.id }), { preserveScroll: true });
    }
};

const showCreateUserForm = ref(false);
const createUserForm = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
});

const submitCreateUser = () => {
    createUserForm.post(route('admin.users.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createUserForm.reset();
            showCreateUserForm.value = false;
        },
    });
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-theme-text-primary">
                Site Administration
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-4 border-b border-theme-border">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button @click="activeTab = 'groups'" :class="[activeTab === 'groups' ? 'border-theme-accent text-theme-accent-text' : 'border-transparent text-theme-text-muted hover:border-theme-border hover:text-theme-text-secondary', 'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium']">Group Management</button>
                        <button @click="activeTab = 'users'" :class="[activeTab === 'users' ? 'border-theme-accent text-theme-accent-text' : 'border-transparent text-theme-text-muted hover:border-theme-border hover:text-theme-text-secondary', 'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium']">Global Users</button>
                        <button @click="activeTab = 'settings'" :class="[activeTab === 'settings' ? 'border-theme-accent text-theme-accent-text' : 'border-transparent text-theme-text-muted hover:border-theme-border hover:text-theme-text-secondary', 'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium']">Settings</button>
                        <Link :href="route('admin.victory-games.index')" class="whitespace-nowrap border-b-2 border-transparent text-theme-text-muted hover:border-theme-border hover:text-theme-text-secondary py-4 px-1 text-sm font-medium">Victory Games</Link>
                    </nav>
                </div>

                <div class="overflow-hidden bg-theme-card shadow-sm sm:rounded-lg">
                    <div class="p-6 text-theme-text-primary">
                        <!-- Groups Management -->
                        <div v-if="activeTab === 'groups'" class="space-y-4">
                            <h3 class="text-2xl font-bold">All Groups</h3>
                             <div v-for="group in groups" :key="group.id" class="bg-theme-page/50 rounded-lg">
                                <button @click="toggleGroup(group.id)" class="w-full p-4 text-left flex justify-between items-center">
                                    <h4 class="text-xl font-semibold">{{ group.name }} <span class="text-sm text-theme-text-muted">({{ group.users.length }} members)</span></h4>
                                    <svg class="w-6 h-6 transform transition-transform" :class="{'rotate-180': openGroup === group.id}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div v-if="openGroup === group.id">
                                    <GroupAdminPanel :group="group" />
                                </div>
                            </div>
                        </div>

                        <!-- Settings -->
                        <div v-if="activeTab === 'settings'">
                            <h3 class="text-2xl font-bold">Site Settings</h3>
                            <div class="mt-6 space-y-4">
                                <div class="flex items-center justify-between p-4 bg-theme-elevated rounded-lg">
                                    <div>
                                        <h4 class="text-base font-semibold text-theme-text-primary">Groups Feature</h4>
                                        <p class="mt-1 text-sm text-theme-text-muted">When disabled, all groups routes return 404 and groups are hidden from navigation.</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="settingsForm.groups_enabled = !settingsForm.groups_enabled; saveSettings()"
                                        :class="[settingsForm.groups_enabled ? 'bg-theme-accent' : 'bg-theme-border', 'relative ml-6 inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-theme-accent-ring focus:ring-offset-2']"
                                        role="switch"
                                        :aria-checked="settingsForm.groups_enabled"
                                    >
                                        <span :class="[settingsForm.groups_enabled ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                                    </button>
                                </div>

                                <!-- Site Logo -->
                                <div class="p-4 bg-theme-elevated rounded-lg">
                                    <h4 class="text-base font-semibold text-theme-text-primary mb-1">Site Logo</h4>
                                    <p class="text-sm text-theme-text-muted mb-4">Upload a PNG, JPG, SVG, or WebP. Displayed in the nav and footer. Max 2 MB.</p>
                                    <div class="flex items-center gap-6 flex-wrap">
                                        <div class="shrink-0">
                                            <p class="text-xs text-theme-text-muted mb-1">Current</p>
                                            <img :src="currentLogoUrl || '/images/ai-survival-mag-logo.svg'" alt="Current logo" class="h-12 w-auto object-contain rounded border border-theme-border bg-theme-card p-1" />
                                        </div>
                                        <div v-if="logoPreview" class="shrink-0">
                                            <p class="text-xs text-theme-text-muted mb-1">Preview</p>
                                            <img :src="logoPreview" alt="Preview" class="h-12 w-auto object-contain rounded border border-theme-border bg-theme-card p-1" />
                                        </div>
                                    </div>
                                    <form @submit.prevent="submitLogo" class="mt-4 flex items-center gap-3 flex-wrap">
                                        <input type="file" accept="image/*" @change="onLogoChange" class="text-sm text-theme-text-secondary" required />
                                        <button type="submit" :disabled="logoForm.processing || !logoForm.logo" class="px-4 py-2 rounded-lg bg-theme-btn-primary text-theme-btn-primary-text text-sm font-semibold hover:bg-theme-btn-primary-hover transition disabled:opacity-50">
                                            Upload Logo
                                        </button>
                                        <p v-if="logoForm.errors.logo" class="text-xs text-danger w-full">{{ logoForm.errors.logo }}</p>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Global Users Table -->
                        <div v-if="activeTab === 'users'">
                            <div class="flex items-center justify-between">
                                <h3 class="text-2xl font-bold">Global User Management</h3>
                                <button @click="showCreateUserForm = !showCreateUserForm" class="px-4 py-2 bg-theme-accent text-white rounded-md text-sm font-medium hover:bg-theme-accent-hover transition">
                                    {{ showCreateUserForm ? 'Cancel' : '+ Create User' }}
                                </button>
                            </div>

                            <div v-if="showCreateUserForm" class="mt-4 p-4 bg-theme-elevated rounded-lg">
                                <h4 class="text-base font-semibold text-theme-text-primary mb-4">New User</h4>
                                <form @submit.prevent="submitCreateUser" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-theme-text-secondary mb-1">Name</label>
                                        <input v-model="createUserForm.name" type="text" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" required />
                                        <p v-if="createUserForm.errors.name" class="mt-1 text-xs text-theme-danger">{{ createUserForm.errors.name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-theme-text-secondary mb-1">Username</label>
                                        <input v-model="createUserForm.username" type="text" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" required />
                                        <p v-if="createUserForm.errors.username" class="mt-1 text-xs text-theme-danger">{{ createUserForm.errors.username }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-theme-text-secondary mb-1">Email</label>
                                        <input v-model="createUserForm.email" type="email" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" required />
                                        <p v-if="createUserForm.errors.email" class="mt-1 text-xs text-theme-danger">{{ createUserForm.errors.email }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-theme-text-secondary mb-1">Password</label>
                                        <input v-model="createUserForm.password" type="password" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" required />
                                        <p v-if="createUserForm.errors.password" class="mt-1 text-xs text-theme-danger">{{ createUserForm.errors.password }}</p>
                                    </div>
                                    <div class="sm:col-span-2 flex justify-end">
                                        <button type="submit" :disabled="createUserForm.processing" class="px-6 py-2 bg-theme-accent text-white rounded-md text-sm font-medium hover:bg-theme-accent-hover transition disabled:opacity-50">
                                            Create User
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="mt-6 overflow-x-auto">
                                <table class="min-w-full divide-y divide-theme-border">
                                    <thead class="bg-theme-elevated">
                                        <tr>
                                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold sm:pl-6">Name</th>
                                            <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold">Site Admin</th>
                                            <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold">Image Perms</th>
                                            <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold">Video Perms</th>
                                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-theme-border bg-theme-page">
                                        <tr v-for="user in users" :key="user.id">
                                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium sm:pl-6">{{ user.name }} ({{ user.username }})</td>
                                            <td class="whitespace-nowrap px-3 py-4 text-center text-sm">
                                                <input type="checkbox" v-model="user.is_admin" class="h-4 w-4 rounded border-theme-border bg-theme-card text-theme-accent focus:ring-theme-accent-ring" />
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-center text-sm">
                                                <input type="checkbox" v-model="user.can_post_images" @change="updateMediaPermissions(user)" class="h-4 w-4 rounded border-theme-border bg-theme-card text-theme-accent focus:ring-theme-accent-ring" />
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-4 text-center text-sm">
                                                <input type="checkbox" v-model="user.can_post_videos" @change="updateMediaPermissions(user)" class="h-4 w-4 rounded border-theme-border bg-theme-card text-theme-accent focus:ring-theme-accent-ring" />
                                            </td>
                                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-2">
                                                <button @click="deleteUser(user)" class="text-theme-danger hover:text-theme-danger-hover">Delete</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
