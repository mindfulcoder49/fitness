<script setup>
import { ref, onMounted, nextTick } from 'vue';

const props = defineProps({
    html: String,
});

const contentRef = ref(null);

function detectPlatform(url) {
    if (/twitter\.com|x\.com/.test(url)) return 'twitter';
    if (/instagram\.com/.test(url)) return 'instagram';
    if (/facebook\.com/.test(url)) return 'facebook';
    return null;
}

function loadScript(src, id) {
    if (document.getElementById(id)) return;
    const script = document.createElement('script');
    script.id = id;
    script.src = src;
    script.async = true;
    document.body.appendChild(script);
}

function processEmbeds() {
    if (!contentRef.value) return;

    const embedNodes = contentRef.value.querySelectorAll('[data-embed]');
    embedNodes.forEach((node) => {
        const url = node.getAttribute('data-embed-url');
        const platform = node.getAttribute('data-embed-platform') || detectPlatform(url);

        if (!url) return;

        node.innerHTML = '';

        if (platform === 'twitter') {
            const blockquote = document.createElement('blockquote');
            blockquote.className = 'twitter-tweet';
            const a = document.createElement('a');
            a.href = url;
            blockquote.appendChild(a);
            node.appendChild(blockquote);
            loadScript('https://platform.twitter.com/widgets.js', 'twitter-wjs');
            if (window.twttr?.widgets) {
                window.twttr.widgets.load(node);
            }
        } else if (platform === 'instagram') {
            const blockquote = document.createElement('blockquote');
            blockquote.className = 'instagram-media';
            blockquote.setAttribute('data-instgrm-permalink', url);
            blockquote.setAttribute('data-instgrm-version', '14');
            node.appendChild(blockquote);
            loadScript('https://www.instagram.com/embed.js', 'instagram-embed');
            if (window.instgrm?.Embeds) {
                window.instgrm.Embeds.process();
            }
        } else if (platform === 'facebook') {
            const div = document.createElement('div');
            div.className = 'fb-post';
            div.setAttribute('data-href', url);
            div.setAttribute('data-width', '100%');
            node.appendChild(div);
            loadScript('https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v18.0', 'facebook-jssdk');
            if (window.FB?.XFBML) {
                window.FB.XFBML.parse(node);
            }
        }
    });
}

onMounted(() => {
    nextTick(processEmbeds);
});
</script>

<template>
    <div
        ref="contentRef"
        class="prose prose-lg max-w-none text-theme-text-primary prose-headings:text-theme-text-primary prose-a:text-theme-link prose-strong:text-theme-text-primary prose-code:text-theme-accent"
        v-html="html"
    />
</template>
