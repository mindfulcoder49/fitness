<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    articles: Object,
    sections: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const sectionId = ref(props.filters?.section_id || '');
const sortBy = ref(props.filters?.sort_by || 'published_at');
const sortDir = ref(props.filters?.sort_dir || 'desc');

function applyFilters() {
    router.get(route('admin.magazine.articles.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
        section_id: sectionId.value || undefined,
        sort_by: sortBy.value,
        sort_dir: sortDir.value,
    }, { preserveState: true });
}

function sortBy_(column) {
    if (sortBy.value === column) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortDir.value = 'asc';
    }
    applyFilters();
}

function sortIcon(column) {
    if (sortBy.value !== column) return '↕';
    return sortDir.value === 'asc' ? '↑' : '↓';
}

let debounceTimer;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 300);
});

function deleteArticle(article) {
    if (confirm(`Delete "${article.title}"?`)) {
        router.delete(route('admin.magazine.articles.destroy', article.id));
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function contributorNames(article) {
    if (!article.contributors?.length) return '-';
    return article.contributors.map(c => c.name).join(', ');
}

const statusColors = {
    draft: 'bg-theme-text-muted/20 text-theme-text-muted',
    scheduled: 'bg-theme-warning/20 text-theme-warning',
    published: 'bg-theme-success/20 text-theme-success',
    archived: 'bg-theme-danger/20 text-theme-danger',
};
</script>

<template>
    <Head title="Magazine Articles" />

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-theme-text-primary">Articles</h1>
            <div class="flex gap-3">
                <Link :href="route('admin.magazine.sections.index')" class="px-4 py-2 bg-theme-btn-secondary text-theme-btn-secondary-text rounded-md text-sm font-medium hover:bg-theme-btn-secondary-hover transition">
                    Sections
                </Link>
                <Link :href="route('admin.magazine.tags.index')" class="px-4 py-2 bg-theme-btn-secondary text-theme-btn-secondary-text rounded-md text-sm font-medium hover:bg-theme-btn-secondary-hover transition">
                    Tags
                </Link>
                <Link :href="route('admin.magazine.contributors.index')" class="px-4 py-2 bg-theme-btn-secondary text-theme-btn-secondary-text rounded-md text-sm font-medium hover:bg-theme-btn-secondary-hover transition">
                    Contributors
                </Link>
                <Link :href="route('admin.magazine.articles.create')" class="px-4 py-2 bg-theme-accent text-white rounded-md text-sm font-medium hover:bg-theme-accent-hover transition">
                    New Article
                </Link>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3 mb-6">
            <input v-model="search" placeholder="Search title, section, contributor…" class="bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm w-72" />
            <select v-model="status" @change="applyFilters" class="bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm">
                <option value="">All statuses</option>
                <option value="draft">Draft</option>
                <option value="scheduled">Scheduled</option>
                <option value="published">Published</option>
                <option value="archived">Archived</option>
            </select>
            <select v-model="sectionId" @change="applyFilters" class="bg-theme-input border-theme-border text-theme-text-primary rounded-md text-sm">
                <option value="">All sections</option>
                <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
        </div>

        <!-- Articles Table -->
        <div class="bg-theme-card border border-theme-border rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-theme-border">
                        <th class="text-left px-4 py-3 text-theme-text-muted font-medium">
                            <button @click="sortBy_('title')" class="flex items-center gap-1 hover:text-theme-text-primary transition">
                                Title <span class="text-xs opacity-60">{{ sortIcon('title') }}</span>
                            </button>
                        </th>
                        <th class="text-left px-4 py-3 text-theme-text-muted font-medium">
                            <button @click="sortBy_('section')" class="flex items-center gap-1 hover:text-theme-text-primary transition">
                                Section <span class="text-xs opacity-60">{{ sortIcon('section') }}</span>
                            </button>
                        </th>
                        <th class="text-left px-4 py-3 text-theme-text-muted font-medium">
                            <button @click="sortBy_('contributor')" class="flex items-center gap-1 hover:text-theme-text-primary transition">
                                Contributor <span class="text-xs opacity-60">{{ sortIcon('contributor') }}</span>
                            </button>
                        </th>
                        <th class="text-center px-4 py-3 text-theme-text-muted font-medium">
                            <button @click="sortBy_('status')" class="flex items-center gap-1 mx-auto hover:text-theme-text-primary transition">
                                Status <span class="text-xs opacity-60">{{ sortIcon('status') }}</span>
                            </button>
                        </th>
                        <th class="text-center px-4 py-3 text-theme-text-muted font-medium">
                            <button @click="sortBy_('access')" class="flex items-center gap-1 mx-auto hover:text-theme-text-primary transition">
                                Access <span class="text-xs opacity-60">{{ sortIcon('access') }}</span>
                            </button>
                        </th>
                        <th class="text-left px-4 py-3 text-theme-text-muted font-medium">
                            <button @click="sortBy_('published_at')" class="flex items-center gap-1 hover:text-theme-text-primary transition">
                                Published <span class="text-xs opacity-60">{{ sortIcon('published_at') }}</span>
                            </button>
                        </th>
                        <th class="text-right px-4 py-3 text-theme-text-muted font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-theme-border">
                    <tr v-for="article in articles.data" :key="article.id">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span v-if="article.is_featured" class="text-theme-warning text-xs" title="Featured">&#9733;</span>
                                <span class="text-theme-text-primary font-medium">{{ article.title }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-theme-text-muted">{{ article.section?.name }}</td>
                        <td class="px-4 py-3 text-theme-text-muted">{{ contributorNames(article) }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs px-2 py-0.5 rounded" :class="statusColors[article.status]">{{ article.status }}</span>
                        </td>
                        <td class="px-4 py-3 text-center text-theme-text-muted">{{ article.access }}</td>
                        <td class="px-4 py-3 text-theme-text-muted">{{ formatDate(article.published_at) }}</td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="route('admin.magazine.articles.edit', article.id)" class="text-xs text-theme-link hover:text-theme-link-hover mr-3">Edit</Link>
                            <button @click="deleteArticle(article)" class="text-xs text-theme-danger hover:text-theme-danger-hover">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="!articles.data?.length" class="text-center py-8 text-theme-text-muted">No articles found.</div>
        </div>

        <!-- Pagination -->
        <div v-if="articles.links?.length > 3" class="mt-6 flex justify-center gap-2">
            <template v-for="link in articles.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="px-3 py-1.5 text-sm rounded border transition"
                    :class="link.active ? 'bg-theme-accent text-white border-theme-accent' : 'border-theme-border text-theme-text-muted hover:text-theme-text-primary'"
                    v-html="link.label"
                />
                <span v-else class="px-3 py-1.5 text-sm text-theme-text-faint" v-html="link.label" />
            </template>
        </div>
    </div>
</template>
