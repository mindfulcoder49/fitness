<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

defineOptions({ layout: AppLayout });

defineProps({
    tags: Array,
});

const showCreateForm = ref(false);
const editingTag = ref(null);

const createForm = useForm({ name: '', slug: '' });
const editForm = useForm({ name: '', slug: '' });

function createTag() {
    createForm.post(route('admin.magazine.tags.store'), {
        onSuccess: () => { createForm.reset(); showCreateForm.value = false; },
    });
}

function startEdit(tag) {
    editingTag.value = tag.id;
    editForm.name = tag.name;
    editForm.slug = tag.slug;
}

function updateTag(tag) {
    editForm.patch(route('admin.magazine.tags.update', tag.id), {
        onSuccess: () => { editingTag.value = null; },
    });
}

function deleteTag(tag) {
    if (confirm(`Delete tag "${tag.name}"?`)) {
        router.delete(route('admin.magazine.tags.destroy', tag.id));
    }
}
</script>

<template>
    <Head title="Magazine Tags" />

    <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-theme-text-primary">Tags</h1>
            <button @click="showCreateForm = !showCreateForm" class="px-4 py-2 bg-theme-accent text-white rounded-md text-sm font-medium hover:bg-theme-accent-hover transition">
                Add Tag
            </button>
        </div>

        <!-- Create Form -->
        <div v-if="showCreateForm" class="mb-6 p-4 bg-theme-card border border-theme-border rounded-lg">
            <form @submit.prevent="createTag" class="flex items-center gap-3">
                <input v-model="createForm.name" placeholder="Tag name" class="flex-1 bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" />
                <input v-model="createForm.slug" placeholder="Slug (auto)" class="flex-1 bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" />
                <button type="submit" :disabled="createForm.processing" class="px-4 py-2 bg-theme-accent text-white rounded text-sm hover:bg-theme-accent-hover transition">Create</button>
                <button type="button" @click="showCreateForm = false" class="px-4 py-2 bg-theme-btn-secondary text-theme-btn-secondary-text rounded text-sm">Cancel</button>
            </form>
        </div>

        <!-- Tags Table -->
        <div class="bg-theme-card border border-theme-border rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-theme-border">
                        <th class="text-left px-4 py-3 text-theme-text-muted font-medium">Name</th>
                        <th class="text-left px-4 py-3 text-theme-text-muted font-medium">Slug</th>
                        <th class="text-center px-4 py-3 text-theme-text-muted font-medium">Articles</th>
                        <th class="text-right px-4 py-3 text-theme-text-muted font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-theme-border">
                    <tr v-for="tag in tags" :key="tag.id">
                        <template v-if="editingTag !== tag.id">
                            <td class="px-4 py-3 text-theme-text-primary">{{ tag.name }}</td>
                            <td class="px-4 py-3 text-theme-text-muted">{{ tag.slug }}</td>
                            <td class="px-4 py-3 text-center text-theme-text-muted">{{ tag.articles_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <button @click="startEdit(tag)" class="text-xs text-theme-link hover:text-theme-link-hover mr-3">Edit</button>
                                <button @click="deleteTag(tag)" class="text-xs text-theme-danger hover:text-theme-danger-hover">Delete</button>
                            </td>
                        </template>
                        <template v-else>
                            <td class="px-4 py-3"><input v-model="editForm.name" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded text-sm" /></td>
                            <td class="px-4 py-3"><input v-model="editForm.slug" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded text-sm" /></td>
                            <td class="px-4 py-3 text-center text-theme-text-muted">{{ tag.articles_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <button @click="updateTag(tag)" class="text-xs text-theme-link hover:text-theme-link-hover mr-3">Save</button>
                                <button @click="editingTag = null" class="text-xs text-theme-text-muted">Cancel</button>
                            </td>
                        </template>
                    </tr>
                </tbody>
            </table>
            <div v-if="!tags?.length" class="text-center py-8 text-theme-text-muted">No tags yet.</div>
        </div>
    </div>
</template>
