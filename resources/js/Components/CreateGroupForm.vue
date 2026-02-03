<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: Boolean,
});

const user = usePage().props.auth.user;

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    description: '',
    is_public: true,
    min_members: null,
});

const createGroup = () => {
    form.post(route('groups.store'), {
        onSuccess: () => emit('close'),
    });
};
</script>

<template>
    <Modal :show="show" @close="$emit('close')">
        <div class="p-6 bg-theme-card text-theme-text-primary">
            <h2 class="text-2xl font-bold mb-4">Create a New Group</h2>
            <form @submit.prevent="createGroup">
                <div>
                    <InputLabel for="name" value="Group Name" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full border-theme-border bg-theme-input text-theme-text-secondary focus:border-theme-accent focus:ring-theme-accent-ring rounded-md shadow-sm" required autofocus />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div class="mt-4">
                    <InputLabel for="description" value="Description" />
                    <textarea id="description" v-model="form.description" class="mt-1 block w-full border-theme-border bg-theme-input text-theme-text-secondary focus:border-theme-accent focus:ring-theme-accent-ring rounded-md shadow-sm"></textarea>
                    <InputError class="mt-2" :message="form.errors.description" />
                </div>

                <div class="mt-4">
                    <InputLabel for="min_members" value="Minimum members to launch (optional)" />
                    <TextInput id="min_members" v-model="form.min_members" type="number" min="1" class="mt-1 block w-full border-theme-border bg-theme-input text-theme-text-secondary focus:border-theme-accent focus:ring-theme-accent-ring rounded-md shadow-sm" placeholder="e.g. 5" />
                    <p class="text-xs text-theme-text-faint mt-1">If set, the group will show as "Gathering Members" until this many people have joined.</p>
                    <InputError class="mt-2" :message="form.errors.min_members" />
                </div>

                <div v-if="user.is_admin" class="block mt-4">
                    <label class="flex items-center">
                        <Checkbox v-model:checked="form.is_public" name="is_public" />
                        <span class="ms-2 text-sm text-theme-text-muted">Make this group public</span>
                    </label>
                    <p class="text-xs text-theme-text-faint mt-1">Public groups are visible to everyone and anyone can join.</p>
                </div>
                <div v-else class="mt-4 p-3 bg-theme-elevated rounded-md">
                     <p class="text-sm text-theme-text-secondary">Your group will be created as private. A site administrator will need to review it and make it public.</p>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="$emit('close')">Cancel</SecondaryButton>
                    <PrimaryButton class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Create Group
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
