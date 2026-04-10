<script setup>
import { usePage } from '@inertiajs/vue3';
const logoUrl = usePage().props.logoUrl;
</script>

<template>
    <img
        :src="logoUrl || '/images/ai-survival-mag-logo.svg'"
        :alt="`${$page.props.appName} logo`"
        class="object-contain"
    >
</template>
