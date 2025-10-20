<script setup>
import { computed, watch, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const show = ref(false);
const message = ref('');
const type = ref('success');

const flash = computed(() => page.props.flash);

watch(flash, (newFlash) => {
    if (newFlash && (newFlash.success || newFlash.error)) {
        message.value = newFlash.success || newFlash.error;
        type.value = newFlash.success ? 'success' : 'error';
        show.value = true;
        setTimeout(() => {
            show.value = false;
        }, 3000);
    }
}, { deep: true });

const alertClasses = computed(() => {
    return {
        'bg-green-500 border-green-600': type.value === 'success',
        'bg-red-500 border-red-600': type.value === 'error',
    };
});
</script>

<template>
    <transition
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        <div v-if="show" :class="alertClasses" class="fixed bottom-4 right-4 z-50 max-w-sm rounded-lg border p-4 text-white shadow-lg">
            <p>{{ message }}</p>
        </div>
    </transition>
</template>
