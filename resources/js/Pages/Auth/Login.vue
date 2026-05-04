<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    magicLinkStatus: {
        type: String,
        default: null,
    },
});

const showMagicLink = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const magicForm = useForm({
    email: '',
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const sendMagicLink = () => {
    magicForm.post(route('magic-link.send'), {
        onSuccess: () => magicForm.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <div v-if="magicLinkStatus" class="mb-4 text-sm font-medium text-green-600">
            {{ magicLinkStatus }}
        </div>

        <!-- Password login form -->
        <form v-if="!showMagicLink" @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-theme-text-muted">Remember me</span>
                </label>
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-sm text-theme-text-muted underline hover:text-theme-text-primary focus:outline-none focus:ring-2 focus:ring-theme-accent-ring focus:ring-offset-2"
                >
                    Forgot your password?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Log in
                </PrimaryButton>
            </div>
        </form>

        <!-- Magic link form -->
        <form v-else @submit.prevent="sendMagicLink">
            <div>
                <InputLabel for="magic-email" value="Email" />

                <TextInput
                    id="magic-email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="magicForm.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="magicForm.errors.email" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <PrimaryButton
                    :class="{ 'opacity-25': magicForm.processing }"
                    :disabled="magicForm.processing"
                >
                    Send magic link
                </PrimaryButton>
            </div>
        </form>

        <!-- Toggle -->
        <div class="mt-6 flex items-center gap-2">
            <div class="flex-1 border-t border-theme-border" />
            <span class="text-xs text-theme-text-muted uppercase tracking-wide">or</span>
            <div class="flex-1 border-t border-theme-border" />
        </div>

        <div class="mt-4 text-center">
            <button
                type="button"
                class="text-sm text-theme-text-muted underline hover:text-theme-text-primary focus:outline-none"
                @click="showMagicLink = !showMagicLink"
            >
                {{ showMagicLink ? 'Log in with password instead' : 'Send me a magic link' }}
            </button>
        </div>
    </GuestLayout>
</template>
