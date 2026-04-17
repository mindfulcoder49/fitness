<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    sections: Array,
});

const showCreateForm = ref(false);
const editingSection = ref(null);
const showVerticalForm = ref(null);

const createForm = useForm({
    name: '',
    slug: '',
    description: '',
    homepage_article_limit: 4,
    is_active: true,
});

const editForm = useForm({
    name: '',
    slug: '',
    description: '',
    homepage_article_limit: 4,
    is_active: true,
});

const verticalForm = useForm({
    section_id: null,
    name: '',
    slug: '',
    description: '',
    image: null,
});

function createSection() {
    createForm.post(route('admin.magazine.sections.store'), {
        onSuccess: () => {
            createForm.reset();
            showCreateForm.value = false;
        },
    });
}

function startEdit(section) {
    editingSection.value = section.id;
    editForm.name = section.name;
    editForm.slug = section.slug;
    editForm.description = section.description || '';
    editForm.homepage_article_limit = section.homepage_article_limit ?? 4;
    editForm.is_active = section.is_active;
}

function updateSection(section) {
    editForm.patch(route('admin.magazine.sections.update', section.id), {
        onSuccess: () => { editingSection.value = null; },
    });
}

function deleteSection(section) {
    if (confirm(`Delete "${section.name}"? This will delete all articles in this section.`)) {
        router.delete(route('admin.magazine.sections.destroy', section.id));
    }
}

function reorder(fromIndex, direction) {
    const order = props.sections.map(s => s.id);
    const toIndex = fromIndex + direction;
    if (toIndex < 0 || toIndex >= order.length) return;
    [order[fromIndex], order[toIndex]] = [order[toIndex], order[fromIndex]];
    router.post(route('admin.magazine.sections.reorder'), { order });
}

function startVerticalCreate(section) {
    showVerticalForm.value = section.id;
    verticalForm.section_id = section.id;
    verticalForm.reset('name', 'slug', 'description', 'image');
}

function createVertical() {
    verticalForm.post(route('admin.magazine.verticals.store'), {
        onSuccess: () => { showVerticalForm.value = null; verticalForm.reset(); },
    });
}

function deleteVertical(vertical) {
    if (confirm(`Delete vertical "${vertical.name}"?`)) {
        router.delete(route('admin.magazine.verticals.destroy', vertical.id));
    }
}
</script>

<template>
    <Head title="Magazine Sections" />

    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-theme-text-primary">Sections</h1>
            <button
                @click="showCreateForm = !showCreateForm"
                class="px-4 py-2 bg-theme-accent text-white rounded-md text-sm font-medium hover:bg-theme-accent-hover transition"
            >
                Add Section
            </button>
        </div>

        <!-- Create Form -->
        <div v-if="showCreateForm" class="mb-6 p-4 bg-theme-card border border-theme-border rounded-lg">
            <form @submit.prevent="createSection" class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <input v-model="createForm.name" placeholder="Section name" class="bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" />
                    <input v-model="createForm.slug" placeholder="Slug (auto-generated)" class="bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" />
                </div>
                <textarea v-model="createForm.description" placeholder="Description (optional)" rows="2" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" />
                <div>
                    <label class="block text-xs text-theme-text-faint mb-1">Homepage Article Count</label>
                    <input v-model.number="createForm.homepage_article_limit" type="number" min="0" max="12" class="w-32 bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" />
                </div>
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 text-sm text-theme-text-secondary">
                        <input type="checkbox" v-model="createForm.is_active" class="rounded border-theme-border text-theme-accent" />
                        Active
                    </label>
                    <button type="submit" :disabled="createForm.processing" class="px-4 py-1.5 bg-theme-accent text-white rounded text-sm hover:bg-theme-accent-hover transition">
                        Create
                    </button>
                    <button type="button" @click="showCreateForm = false" class="px-4 py-1.5 bg-theme-btn-secondary text-theme-btn-secondary-text rounded text-sm hover:bg-theme-btn-secondary-hover transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- Sections List -->
        <div class="space-y-4">
            <div v-for="(section, index) in sections" :key="section.id" class="bg-theme-card border border-theme-border rounded-lg p-4">
                <div v-if="editingSection !== section.id" class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex flex-col gap-0.5">
                            <button @click="reorder(index, -1)" :disabled="index === 0" class="text-theme-text-faint hover:text-theme-text-primary disabled:opacity-30 text-xs">&uarr;</button>
                            <button @click="reorder(index, 1)" :disabled="index === sections.length - 1" class="text-theme-text-faint hover:text-theme-text-primary disabled:opacity-30 text-xs">&darr;</button>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-semibold text-theme-text-primary">{{ section.name }}</h3>
                                <span v-if="!section.is_active" class="text-xs px-1.5 py-0.5 bg-theme-danger/20 text-theme-danger rounded">Inactive</span>
                                <span class="text-xs text-theme-text-faint">({{ section.articles_count }} articles)</span>
                                <span class="text-xs text-theme-text-faint">Front page: {{ section.homepage_article_limit }}</span>
                            </div>
                            <p v-if="section.description" class="text-sm text-theme-text-muted mt-0.5">{{ section.description }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Link :href="route('admin.magazine.sections.articles', section.id)" class="text-xs text-theme-link hover:text-theme-link-hover">Arrange Articles</Link>
                        <button @click="startVerticalCreate(section)" class="text-xs text-theme-link hover:text-theme-link-hover">+ Vertical</button>
                        <button @click="startEdit(section)" class="text-xs text-theme-link hover:text-theme-link-hover">Edit</button>
                        <button @click="deleteSection(section)" class="text-xs text-theme-danger hover:text-theme-danger-hover">Delete</button>
                    </div>
                </div>

                <!-- Edit Form -->
                <form v-else @submit.prevent="updateSection(section)" class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <input v-model="editForm.name" class="bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" />
                        <input v-model="editForm.slug" class="bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" />
                    </div>
                    <textarea v-model="editForm.description" rows="2" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" />
                    <div>
                        <label class="block text-xs text-theme-text-faint mb-1">Homepage Article Count</label>
                        <input v-model.number="editForm.homepage_article_limit" type="number" min="0" max="12" class="w-32 bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" />
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2 text-sm text-theme-text-secondary">
                            <input type="checkbox" v-model="editForm.is_active" class="rounded border-theme-border text-theme-accent" />
                            Active
                        </label>
                        <button type="submit" class="px-4 py-1.5 bg-theme-accent text-white rounded text-sm hover:bg-theme-accent-hover transition">Save</button>
                        <button type="button" @click="editingSection = null" class="px-4 py-1.5 bg-theme-btn-secondary text-theme-btn-secondary-text rounded text-sm">Cancel</button>
                    </div>
                </form>

                <!-- Verticals -->
                <div v-if="section.verticals?.length" class="mt-3 ml-8 space-y-2">
                    <div v-for="vertical in section.verticals" :key="vertical.id" class="flex items-center justify-between py-1 px-3 bg-theme-elevated rounded text-sm">
                        <span class="text-theme-text-secondary">{{ vertical.name }} <span class="text-theme-text-faint">({{ vertical.articles_count }} articles)</span></span>
                        <button @click="deleteVertical(vertical)" class="text-xs text-theme-danger hover:text-theme-danger-hover">Delete</button>
                    </div>
                </div>

                <!-- Create Vertical Form -->
                <div v-if="showVerticalForm === section.id" class="mt-3 ml-8 p-3 bg-theme-elevated rounded">
                    <form @submit.prevent="createVertical" class="space-y-2">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <input v-model="verticalForm.name" placeholder="Vertical name" class="bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" />
                            <input v-model="verticalForm.slug" placeholder="Slug (auto)" class="bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" />
                        </div>
                        <textarea v-model="verticalForm.description" placeholder="Description" rows="1" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" />
                        <div class="flex items-center gap-2">
                            <button type="submit" class="px-3 py-1 bg-theme-accent text-white rounded text-sm hover:bg-theme-accent-hover transition">Create</button>
                            <button type="button" @click="showVerticalForm = null" class="px-3 py-1 bg-theme-btn-secondary text-theme-btn-secondary-text rounded text-sm">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div v-if="!sections?.length" class="text-center py-16 text-theme-text-muted">
            No sections yet. Create one to get started.
        </div>
    </div>
</template>
