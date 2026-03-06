<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
    contributors: Array,
    users: Array,
});

const showCreateForm = ref(false);
const editingContributor = ref(null);

const createForm = useForm({
    name: '',
    slug: '',
    bio: '',
    user_id: '',
    photo: null,
});

const editForm = useForm({
    name: '',
    slug: '',
    bio: '',
    user_id: '',
    photo: null,
});

function createContributor() {
    createForm.post(route('admin.magazine.contributors.store'), {
        onSuccess: () => { createForm.reset(); showCreateForm.value = false; },
    });
}

function startEdit(contributor) {
    editingContributor.value = contributor.id;
    editForm.name = contributor.name;
    editForm.slug = contributor.slug;
    editForm.bio = contributor.bio || '';
    editForm.user_id = contributor.user_id || '';
    editForm.photo = null;
}

function updateContributor(contributor) {
    editForm.patch(route('admin.magazine.contributors.update', contributor.id), {
        onSuccess: () => { editingContributor.value = null; },
    });
}

function deleteContributor(contributor) {
    if (confirm(`Delete contributor "${contributor.name}"?`)) {
        router.delete(route('admin.magazine.contributors.destroy', contributor.id));
    }
}
</script>

<template>
    <Head title="Magazine Contributors" />

    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-theme-text-primary">Contributors</h1>
            <button @click="showCreateForm = !showCreateForm" class="px-4 py-2 bg-theme-accent text-white rounded-md text-sm font-medium hover:bg-theme-accent-hover transition">
                Add Contributor
            </button>
        </div>

        <!-- Create Form -->
        <div v-if="showCreateForm" class="mb-6 p-4 bg-theme-card border border-theme-border rounded-lg">
            <form @submit.prevent="createContributor" class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <input v-model="createForm.name" placeholder="Name" class="bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" />
                    <input v-model="createForm.slug" placeholder="Slug (auto)" class="bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" />
                </div>
                <textarea v-model="createForm.bio" placeholder="Bio" rows="3" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <select v-model="createForm.user_id" class="bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm">
                        <option value="">Link to user account (optional)</option>
                        <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }} ({{ user.email }})</option>
                    </select>
                    <input type="file" @input="createForm.photo = $event.target.files[0]" accept="image/*" class="text-sm text-theme-text-secondary" />
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" :disabled="createForm.processing" class="px-4 py-1.5 bg-theme-accent text-white rounded text-sm hover:bg-theme-accent-hover transition">Create</button>
                    <button type="button" @click="showCreateForm = false" class="px-4 py-1.5 bg-theme-btn-secondary text-theme-btn-secondary-text rounded text-sm">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Contributors Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="contributor in contributors" :key="contributor.id" class="bg-theme-card border border-theme-border rounded-lg p-4">
                <template v-if="editingContributor !== contributor.id">
                    <div class="flex items-start gap-3">
                        <img v-if="contributor.photo_url" :src="contributor.photo_url" class="w-12 h-12 rounded-full object-cover" />
                        <div v-else class="w-12 h-12 rounded-full bg-theme-elevated flex items-center justify-center text-lg font-bold text-theme-text-muted">
                            {{ contributor.name.charAt(0) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-theme-text-primary">{{ contributor.name }}</h3>
                            <p class="text-xs text-theme-text-faint">{{ contributor.articles_count }} articles</p>
                            <p v-if="contributor.user" class="text-xs text-theme-text-muted mt-0.5">Linked: {{ contributor.user.name }}</p>
                        </div>
                    </div>
                    <p v-if="contributor.bio" class="mt-2 text-sm text-theme-text-muted line-clamp-2">{{ contributor.bio }}</p>
                    <div class="mt-3 flex gap-3">
                        <button @click="startEdit(contributor)" class="text-xs text-theme-link hover:text-theme-link-hover">Edit</button>
                        <button @click="deleteContributor(contributor)" class="text-xs text-theme-danger hover:text-theme-danger-hover">Delete</button>
                    </div>
                </template>

                <!-- Edit Form -->
                <template v-else>
                    <form @submit.prevent="updateContributor(contributor)" class="space-y-2">
                        <input v-model="editForm.name" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" />
                        <input v-model="editForm.slug" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" />
                        <textarea v-model="editForm.bio" rows="2" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" />
                        <select v-model="editForm.user_id" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm">
                            <option value="">No linked user</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                        </select>
                        <input type="file" @input="editForm.photo = $event.target.files[0]" accept="image/*" class="text-sm text-theme-text-secondary" />
                        <div class="flex gap-2">
                            <button type="submit" class="px-3 py-1 bg-theme-accent text-white rounded text-sm">Save</button>
                            <button type="button" @click="editingContributor = null" class="px-3 py-1 bg-theme-btn-secondary text-theme-btn-secondary-text rounded text-sm">Cancel</button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

        <div v-if="!contributors?.length" class="text-center py-16 text-theme-text-muted">No contributors yet.</div>
    </div>
</template>
