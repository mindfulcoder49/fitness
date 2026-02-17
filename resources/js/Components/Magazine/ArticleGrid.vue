<script setup>
import ArticleCard from './ArticleCard.vue';

defineProps({
    articles: Array,
});
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <ArticleCard v-for="article in articles" :key="article.id" :article="article" />
    </div>
</template>
