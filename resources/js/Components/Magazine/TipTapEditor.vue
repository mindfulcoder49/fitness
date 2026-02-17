<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';
import Youtube from '@tiptap/extension-youtube';
import VideoNode from '@/Extensions/VideoNode';
import AudioNode from '@/Extensions/AudioNode';
import EmbedNode from '@/Extensions/EmbedNode';
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    modelValue: { type: String, default: '' },
    articleId: { type: [Number, String], default: null },
});

const emit = defineEmits(['update:modelValue']);

const imageInput = ref(null);
const videoInput = ref(null);
const audioInput = ref(null);
const uploading = ref(false);

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Image,
        Link.configure({ openOnClick: false }),
        Placeholder.configure({ placeholder: 'Write your article...' }),
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
        Underline,
        Youtube.configure({ inline: false }),
        VideoNode,
        AudioNode,
        EmbedNode,
    ],
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
});

watch(() => props.modelValue, (value) => {
    if (editor.value && editor.value.getHTML() !== value) {
        editor.value.commands.setContent(value, false);
    }
});

function setLink() {
    const url = prompt('Enter URL:');
    if (url) {
        editor.value.chain().focus().setLink({ href: url }).run();
    }
}

async function uploadImage(event) {
    const file = event.target.files[0];
    if (!file || !props.articleId) return;

    uploading.value = true;
    const formData = new FormData();
    formData.append('image', file);

    try {
        const { data } = await axios.post(
            route('admin.magazine.articles.upload-image', props.articleId),
            formData
        );
        editor.value.chain().focus().setImage({ src: data.url }).run();
    } catch (e) {
        alert('Image upload failed.');
    } finally {
        uploading.value = false;
        event.target.value = '';
    }
}

async function uploadVideo(event) {
    const file = event.target.files[0];
    if (!file || !props.articleId) return;

    uploading.value = true;
    const formData = new FormData();
    formData.append('video', file);

    try {
        const { data } = await axios.post(
            route('admin.magazine.articles.upload-video', props.articleId),
            formData
        );
        editor.value.chain().focus().setVideo({ src: data.url }).run();
    } catch (e) {
        alert('Video upload failed.');
    } finally {
        uploading.value = false;
        event.target.value = '';
    }
}

async function uploadAudio(event) {
    const file = event.target.files[0];
    if (!file || !props.articleId) return;

    uploading.value = true;
    const formData = new FormData();
    formData.append('audio', file);

    try {
        const { data } = await axios.post(
            route('admin.magazine.articles.upload-audio', props.articleId),
            formData
        );
        editor.value.chain().focus().setAudio({ src: data.url }).run();
    } catch (e) {
        alert('Audio upload failed.');
    } finally {
        uploading.value = false;
        event.target.value = '';
    }
}

function addEmbed() {
    const url = prompt('Enter embed URL (YouTube, Twitter/X, Instagram, Facebook):');
    if (!url) return;

    // YouTube - use built-in extension
    if (/youtube\.com|youtu\.be/.test(url)) {
        editor.value.chain().focus().setYoutubeVideo({ src: url }).run();
        return;
    }

    // Detect platform
    let platform = null;
    if (/twitter\.com|x\.com/.test(url)) platform = 'twitter';
    else if (/instagram\.com/.test(url)) platform = 'instagram';
    else if (/facebook\.com/.test(url)) platform = 'facebook';

    if (platform) {
        editor.value.chain().focus().setEmbed({ url, platform }).run();
    } else {
        alert('Unsupported embed URL. Supported: YouTube, Twitter/X, Instagram, Facebook.');
    }
}

function btn(active) {
    return active
        ? 'p-1.5 rounded bg-theme-accent text-white'
        : 'p-1.5 rounded text-theme-text-muted hover:text-theme-text-primary hover:bg-theme-elevated transition';
}
</script>

<template>
    <div class="border border-theme-border rounded-lg overflow-hidden bg-theme-card">
        <!-- Toolbar -->
        <div v-if="editor" class="flex flex-wrap items-center gap-0.5 p-2 border-b border-theme-border bg-theme-elevated">
            <button type="button" @click="editor.chain().focus().toggleBold().run()" :class="btn(editor.isActive('bold'))" title="Bold">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M15.6 10.79c.97-.67 1.65-1.77 1.65-2.79 0-2.26-1.75-4-4-4H7v14h7.04c2.09 0 3.71-1.7 3.71-3.79 0-1.52-.86-2.82-2.15-3.42zM10 6.5h3c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-3v-3zm3.5 9H10v-3h3.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5z"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().toggleItalic().run()" :class="btn(editor.isActive('italic'))" title="Italic">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M10 4v3h2.21l-3.42 8H6v3h8v-3h-2.21l3.42-8H18V4z"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().toggleUnderline().run()" :class="btn(editor.isActive('underline'))" title="Underline">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17c3.31 0 6-2.69 6-6V3h-2.5v8c0 1.93-1.57 3.5-3.5 3.5S8.5 12.93 8.5 11V3H6v8c0 3.31 2.69 6 6 6zm-7 2v2h14v-2H5z"/></svg>
            </button>

            <div class="w-px h-5 bg-theme-border mx-1"></div>

            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" :class="btn(editor.isActive('heading', { level: 2 }))" title="Heading 2">
                <span class="text-xs font-bold">H2</span>
            </button>
            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" :class="btn(editor.isActive('heading', { level: 3 }))" title="Heading 3">
                <span class="text-xs font-bold">H3</span>
            </button>
            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 4 }).run()" :class="btn(editor.isActive('heading', { level: 4 }))" title="Heading 4">
                <span class="text-xs font-bold">H4</span>
            </button>

            <div class="w-px h-5 bg-theme-border mx-1"></div>

            <button type="button" @click="editor.chain().focus().toggleBulletList().run()" :class="btn(editor.isActive('bulletList'))" title="Bullet List">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M4 10.5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0-6c-.83 0-1.5.67-1.5 1.5S3.17 7.5 4 7.5 5.5 6.83 5.5 6 4.83 4.5 4 4.5zm0 12c-.83 0-1.5.68-1.5 1.5s.68 1.5 1.5 1.5 1.5-.68 1.5-1.5-.67-1.5-1.5-1.5zM7 19h14v-2H7v2zm0-6h14v-2H7v2zm0-8v2h14V5H7z"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().toggleOrderedList().run()" :class="btn(editor.isActive('orderedList'))" title="Ordered List">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M2 17h2v.5H3v1h1v.5H2v1h3v-4H2v1zm1-9h1V4H2v1h1v3zm-1 3h1.8L2 13.1v.9h3v-1H3.2L5 10.9V10H2v1zm5-6v2h14V5H7zm0 14h14v-2H7v2zm0-6h14v-2H7v2z"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().toggleBlockquote().run()" :class="btn(editor.isActive('blockquote'))" title="Blockquote">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z"/></svg>
            </button>

            <div class="w-px h-5 bg-theme-border mx-1"></div>

            <button type="button" @click="editor.chain().focus().setTextAlign('left').run()" :class="btn(editor.isActive({ textAlign: 'left' }))" title="Align Left">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M15 15H3v2h12v-2zm0-8H3v2h12V7zM3 13h18v-2H3v2zm0 8h18v-2H3v2zM3 3v2h18V3H3z"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().setTextAlign('center').run()" :class="btn(editor.isActive({ textAlign: 'center' }))" title="Align Center">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M7 15v2h10v-2H7zm-4 6h18v-2H3v2zm0-8h18v-2H3v2zm4-6v2h10V7H7zM3 3v2h18V3H3z"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().setTextAlign('right').run()" :class="btn(editor.isActive({ textAlign: 'right' }))" title="Align Right">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M3 21h18v-2H3v2zm6-4h12v-2H9v2zm-6-4h18v-2H3v2zm6-4h12V7H9v2zM3 3v2h18V3H3z"/></svg>
            </button>

            <div class="w-px h-5 bg-theme-border mx-1"></div>

            <button type="button" @click="setLink" :class="btn(editor.isActive('link'))" title="Link">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>
            </button>

            <!-- Media uploads -->
            <button type="button" @click="imageInput?.click()" :disabled="!articleId || uploading" class="p-1.5 rounded text-theme-text-muted hover:text-theme-text-primary hover:bg-theme-elevated transition disabled:opacity-30" title="Upload Image">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
            </button>
            <button type="button" @click="videoInput?.click()" :disabled="!articleId || uploading" class="p-1.5 rounded text-theme-text-muted hover:text-theme-text-primary hover:bg-theme-elevated transition disabled:opacity-30" title="Upload Video">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>
            </button>
            <button type="button" @click="audioInput?.click()" :disabled="!articleId || uploading" class="p-1.5 rounded text-theme-text-muted hover:text-theme-text-primary hover:bg-theme-elevated transition disabled:opacity-30" title="Upload Audio">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3v9.28c-.47-.17-.97-.28-1.5-.28C8.01 12 6 14.01 6 16.5S8.01 21 10.5 21c2.31 0 4.2-1.75 4.45-4H15V6h4V3h-7z"/></svg>
            </button>
            <button type="button" @click="addEmbed" class="p-1.5 rounded text-theme-text-muted hover:text-theme-text-primary hover:bg-theme-elevated transition" title="Embed (YouTube, Twitter, Instagram, Facebook)">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/></svg>
            </button>

            <div class="w-px h-5 bg-theme-border mx-1"></div>

            <button type="button" @click="editor.chain().focus().undo().run()" class="p-1.5 rounded text-theme-text-muted hover:text-theme-text-primary hover:bg-theme-elevated transition" title="Undo">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().redo().run()" class="p-1.5 rounded text-theme-text-muted hover:text-theme-text-primary hover:bg-theme-elevated transition" title="Redo">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M18.4 10.6C16.55 8.99 14.15 8 11.5 8c-4.65 0-8.58 3.03-9.96 7.22L3.9 16c1.05-3.19 4.05-5.5 7.6-5.5 1.95 0 3.73.72 5.12 1.88L13 16h9V7l-3.6 3.6z"/></svg>
            </button>

            <span v-if="uploading" class="text-xs text-theme-text-muted ml-2">Uploading...</span>
            <span v-if="!articleId" class="text-xs text-theme-text-faint ml-2">Save article first to enable media uploads</span>
        </div>

        <!-- Editor Content -->
        <EditorContent :editor="editor" class="prose prose-lg max-w-none p-4 min-h-[300px] text-theme-text-primary focus:outline-none prose-headings:text-theme-text-primary prose-a:text-theme-link prose-strong:text-theme-text-primary" />

        <!-- Hidden file inputs -->
        <input ref="imageInput" type="file" accept="image/*" class="hidden" @change="uploadImage" />
        <input ref="videoInput" type="file" accept="video/mp4,video/webm,video/quicktime" class="hidden" @change="uploadVideo" />
        <input ref="audioInput" type="file" accept="audio/mpeg,audio/wav,audio/ogg,audio/mp4" class="hidden" @change="uploadAudio" />
    </div>
</template>

<style>
.ProseMirror {
    outline: none;
}
.ProseMirror p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    pointer-events: none;
    height: 0;
    color: rgb(var(--color-text-faint));
}
</style>
