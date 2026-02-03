<script setup>
import { ref, computed, onMounted } from 'vue';
import marked from 'marked';

const props = defineProps({
    modelValue: String,
});

const emit = defineEmits(['update:modelValue']);

const editorRef = ref(null);
const viewMode = ref('split'); // 'edit', 'preview', 'split'

const parsedContent = computed(() => {
    return marked(props.modelValue || '', { breaks: true });
});

const applyFormat = (prefix, suffix = '') => {
    const textarea = editorRef.value;
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = textarea.value.substring(start, end);
    const newText = `${prefix}${selectedText}${suffix}`;
    const updatedContent = textarea.value.substring(0, start) + newText + textarea.value.substring(end);

    emit('update:modelValue', updatedContent);

    // Focus and re-select for better UX
    textarea.focus();
    setTimeout(() => {
        textarea.selectionStart = start + prefix.length;
        textarea.selectionEnd = end + prefix.length;
    }, 0);
};

const insertList = () => {
    const textarea = editorRef.value;
    const start = textarea.selectionStart;
    const selectedText = textarea.value.substring(start, textarea.selectionEnd);
    const lines = selectedText.split('\n');
    const listText = lines.map(line => `* ${line}`).join('\n');
    const updatedContent = textarea.value.substring(0, start) + listText + textarea.value.substring(textarea.selectionEnd);

    emit('update:modelValue', updatedContent);
    textarea.focus();
};

const buttons = [
    { label: 'B', action: () => applyFormat('**', '**'), title: 'Bold' },
    { label: 'I', action: () => applyFormat('*', '*'), title: 'Italic' },
    { label: 'H1', action: () => applyFormat('# '), title: 'Header 1' },
    { label: 'H2', action: () => applyFormat('## '), title: 'Header 2' },
    { label: 'List', action: insertList, title: 'Bullet List' },
];
</script>

<template>
    <div class="border border-theme-border rounded-md">
        <!-- Toolbar -->
        <div class="flex items-center justify-between p-2 bg-theme-elevated border-b border-theme-border">
            <div class="flex items-center space-x-2">
                <button v-for="button in buttons" :key="button.label" @click="button.action" :title="button.title"
                    class="px-3 py-1 text-sm font-semibold text-theme-text-primary bg-theme-card rounded hover:bg-theme-elevated">
                    {{ button.label }}
                </button>
            </div>
            <div class="flex items-center space-x-2">
                <button @click="viewMode = 'edit'" :class="{'bg-theme-accent': viewMode === 'edit'}" class="px-2 py-1 text-xs rounded hover:bg-theme-accent-hover">Edit</button>
                <button @click="viewMode = 'split'" :class="{'bg-theme-accent': viewMode === 'split'}" class="px-2 py-1 text-xs rounded hover:bg-theme-accent-hover">Split</button>
                <button @click="viewMode = 'preview'" :class="{'bg-theme-accent': viewMode === 'preview'}" class="px-2 py-1 text-xs rounded hover:bg-theme-accent-hover">Preview</button>
            </div>
        </div>

        <!-- Editor/Preview Panes -->
        <div class="grid" :class="{
            'grid-cols-1': viewMode !== 'split',
            'grid-cols-2': viewMode === 'split',
        }">
            <!-- Editor -->
            <div v-if="viewMode !== 'preview'">
                <textarea
                    ref="editorRef"
                    :value="modelValue"
                    @input="$emit('update:modelValue', $event.target.value)"
                    class="w-full h-64 p-4 bg-theme-page text-theme-text-primary border-0 focus:ring-0 resize-y"
                    placeholder="Write your post content here..."
                ></textarea>
            </div>

            <!-- Preview -->
            <div v-if="viewMode !== 'edit'" class="p-4 bg-theme-card h-64 overflow-y-auto">
                <div class="prose prose-invert max-w-none" v-html="parsedContent"></div>
            </div>
        </div>
    </div>
</template>
