<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TipTapEditor from '@/Components/Magazine/TipTapEditor.vue';
import { ref, computed, watch } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    article: { type: Object, default: null },
    sections: Array,
    tags: Array,
    contributors: Array,
});

const isEditing = computed(() => !!props.article);

const form = useForm({
    title: props.article?.title || '',
    slug: props.article?.slug || '',
    excerpt: props.article?.excerpt || '',
    content: props.article?.content || '',
    featured_image: null,
    section_id: props.article?.section_id || '',
    vertical_id: props.article?.vertical_id || '',
    status: props.article?.status || 'draft',
    access: props.article?.access || 'public',
    is_featured: props.article?.is_featured || false,
    meta_title: props.article?.meta_title || '',
    meta_description: props.article?.meta_description || '',
    published_at: props.article?.published_at ? props.article.published_at.substring(0, 16) : '',
    tags: props.article?.tags?.map(t => t.id) || [],
    contributors: props.article?.contributors?.map(c => ({
        contributor_id: c.id,
        role: c.pivot?.role || '',
    })) || [{ contributor_id: '', role: '' }],
});

// Auto-generate slug from title
watch(() => form.title, (title) => {
    if (!isEditing.value || !form.slug) {
        form.slug = title
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
    }
});

const availableVerticals = computed(() => {
    if (!form.section_id) return [];
    const section = props.sections.find(s => s.id == form.section_id);
    return section?.verticals || [];
});

watch(() => form.section_id, () => {
    if (!availableVerticals.value.find(v => v.id == form.vertical_id)) {
        form.vertical_id = '';
    }
});

function addContributor() {
    form.contributors.push({ contributor_id: '', role: '' });
}

function removeContributor(index) {
    form.contributors.splice(index, 1);
}

function toggleTag(tagId) {
    const idx = form.tags.indexOf(tagId);
    if (idx > -1) {
        form.tags.splice(idx, 1);
    } else {
        form.tags.push(tagId);
    }
}

function submit() {
    if (isEditing.value) {
        form.post(route('admin.magazine.articles.update', props.article.id), {
            _method: 'patch',
        });
    } else {
        form.post(route('admin.magazine.articles.store'));
    }
}
</script>

<template>
    <Head :title="isEditing ? 'Edit Article' : 'New Article'" />

    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-theme-text-primary">
                {{ isEditing ? 'Edit Article' : 'New Article' }}
            </h1>
            <Link :href="route('admin.magazine.articles.index')" class="text-sm text-theme-link hover:text-theme-link-hover">
                &larr; Back to Articles
            </Link>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Title & Slug -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-theme-text-secondary mb-1">Title</label>
                    <input v-model="form.title" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded-md" />
                    <p v-if="form.errors.title" class="text-xs text-theme-danger mt-1">{{ form.errors.title }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-theme-text-secondary mb-1">Slug</label>
                    <input v-model="form.slug" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded-md" />
                    <p v-if="form.errors.slug" class="text-xs text-theme-danger mt-1">{{ form.errors.slug }}</p>
                </div>
            </div>

            <!-- Excerpt -->
            <div>
                <label class="block text-sm font-medium text-theme-text-secondary mb-1">Excerpt</label>
                <textarea v-model="form.excerpt" rows="2" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded-md" />
            </div>

            <!-- Content (TipTap Editor) -->
            <div>
                <label class="block text-sm font-medium text-theme-text-secondary mb-1">Content</label>
                <TipTapEditor v-model="form.content" :article-id="article?.id" />
                <p v-if="form.errors.content" class="text-xs text-theme-danger mt-1">{{ form.errors.content }}</p>
            </div>

            <!-- Featured Image -->
            <div>
                <label class="block text-sm font-medium text-theme-text-secondary mb-1">Featured Image</label>
                <div v-if="article?.featured_image_url" class="mb-2">
                    <img :src="article.featured_image_url" class="h-32 rounded-md object-cover" />
                </div>
                <input type="file" @input="form.featured_image = $event.target.files[0]" accept="image/*" class="text-sm text-theme-text-secondary" />
            </div>

            <!-- Section & Vertical -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-theme-text-secondary mb-1">Section</label>
                    <select v-model="form.section_id" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded-md">
                        <option value="">Select section</option>
                        <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                    <p v-if="form.errors.section_id" class="text-xs text-theme-danger mt-1">{{ form.errors.section_id }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-theme-text-secondary mb-1">Vertical (optional)</label>
                    <select v-model="form.vertical_id" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded-md" :disabled="!availableVerticals.length">
                        <option value="">None</option>
                        <option v-for="v in availableVerticals" :key="v.id" :value="v.id">{{ v.name }}</option>
                    </select>
                </div>
            </div>

            <!-- Status, Access, Featured, Published At -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-theme-text-secondary mb-1">Status</label>
                    <select v-model="form.status" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded-md">
                        <option value="draft">Draft</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-theme-text-secondary mb-1">Access</label>
                    <select v-model="form.access" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded-md">
                        <option value="public">Public</option>
                        <option value="members">Members Only</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-theme-text-secondary mb-1">Published At</label>
                    <input v-model="form.published_at" type="datetime-local" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded-md" />
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm text-theme-text-secondary pb-2">
                        <input type="checkbox" v-model="form.is_featured" class="rounded border-theme-border text-theme-accent" />
                        Featured
                    </label>
                </div>
            </div>

            <!-- Tags -->
            <div>
                <label class="block text-sm font-medium text-theme-text-secondary mb-2">Tags</label>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="tag in tags"
                        :key="tag.id"
                        type="button"
                        @click="toggleTag(tag.id)"
                        class="px-3 py-1 text-sm rounded-full border transition"
                        :class="form.tags.includes(tag.id)
                            ? 'bg-theme-accent text-white border-theme-accent'
                            : 'border-theme-border text-theme-text-muted hover:text-theme-text-primary'"
                    >
                        {{ tag.name }}
                    </button>
                </div>
            </div>

            <!-- Contributors -->
            <div>
                <label class="block text-sm font-medium text-theme-text-secondary mb-2">Contributors</label>
                <div class="space-y-2">
                    <div v-for="(entry, index) in form.contributors" :key="index" class="flex items-center gap-3">
                        <select v-model="entry.contributor_id" class="flex-1 bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm">
                            <option value="">Select contributor</option>
                            <option v-for="c in contributors" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <input v-model="entry.role" placeholder="Role (e.g., Author, Editor)" class="flex-1 bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" />
                        <button type="button" @click="removeContributor(index)" class="text-theme-danger hover:text-theme-danger-hover text-sm" v-if="form.contributors.length > 1">
                            Remove
                        </button>
                    </div>
                    <button type="button" @click="addContributor" class="text-sm text-theme-link hover:text-theme-link-hover">
                        + Add Contributor
                    </button>
                </div>
            </div>

            <!-- Meta -->
            <div class="border-t border-theme-border pt-6">
                <h3 class="text-sm font-medium text-theme-text-muted mb-3">SEO / Meta</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-theme-text-faint mb-1">Meta Title</label>
                        <input v-model="form.meta_title" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-theme-text-faint mb-1">Meta Description</label>
                        <textarea v-model="form.meta_description" rows="2" class="w-full bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm" />
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center gap-4 pt-4">
                <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-theme-accent text-white rounded-md font-medium hover:bg-theme-accent-hover transition disabled:opacity-50">
                    {{ isEditing ? 'Update Article' : 'Create Article' }}
                </button>
                <Link :href="route('admin.magazine.articles.index')" class="px-6 py-2.5 bg-theme-btn-secondary text-theme-btn-secondary-text rounded-md font-medium hover:bg-theme-btn-secondary-hover transition">
                    Cancel
                </Link>
                <p v-if="form.recentlySuccessful" class="text-sm text-theme-success">Saved.</p>
            </div>
        </form>
    </div>
</template>
