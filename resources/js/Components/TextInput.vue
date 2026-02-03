<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-md border-theme-border bg-theme-input text-theme-text-primary shadow-sm focus:border-theme-accent focus:ring-theme-accent-ring"
        v-model="model"
        ref="input"
    />
</template>
