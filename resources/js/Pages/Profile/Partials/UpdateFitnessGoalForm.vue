<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { Transition } from 'vue';

const user = usePage().props.auth.user;

const form = useForm({
    daily_fitness_goal: user.daily_fitness_goal || '',
});

const submit = () => {
    form.patch(route('profile.update-fitness-goal'), {
        preserveScroll: true,
        onSuccess: () => {},
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Daily Fitness Goal</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Update your daily fitness goal. This is your personal commitment.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div>
                <InputLabel for="daily_fitness_goal" value="Daily Fitness Goal" />

                <TextInput
                    id="daily_fitness_goal"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.daily_fitness_goal"
                    required
                    autofocus
                    placeholder="e.g., 'Run 2 miles every morning' or '30 minutes of yoga'"
                />

                <InputError class="mt-2" :message="form.errors.daily_fitness_goal" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Saved.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
