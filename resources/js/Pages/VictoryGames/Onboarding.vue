<script setup>
import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import axios from 'axios';

defineOptions({ layout: null });

const props = defineProps({
    providers: { type: Array, default: () => [] },
    defaultProvider: { type: String, default: null },
    defaultModel: { type: String, default: null },
    defaultMode: { type: String, default: 'desktop' },
    maxSteps: { type: Number, default: 8 },
});

const page = usePage();

// ── Wizard state ──────────────────────────────────────────────────────────────
const step = ref(1);
const loading = ref(false);
const errors = ref({});

// Step 1
const displayName = ref(page.props.auth?.user?.name ?? '');

// Step 2
const appName = ref('');
const appUrl = ref('');
const victorSlug = ref(null);

// Step 3
const goal = ref('');
const provider = ref(props.defaultProvider ?? props.providers[0]?.key ?? '');
const model = ref(props.defaultModel ?? '');
const mode = ref(props.defaultMode);
const appSlug = ref(null);
const appCurrentUrl = ref(null);

const hasProviders = computed(() => props.providers.length > 0);

watch(provider, (key) => {
    const found = props.providers.find((p) => p.key === key);
    if (found) model.value = found.default_model ?? '';
});

// ── Step handlers ─────────────────────────────────────────────────────────────
async function handleStep1() {
    loading.value = true;
    errors.value = {};
    try {
        const { data } = await axios.post(route('onboarding.victor'), {
            display_name: displayName.value,
        });
        victorSlug.value = data.victor.slug;
        step.value = 2;
    } catch (e) {
        errors.value = e.response?.data?.errors ?? { display_name: ['Something went wrong. Please try again.'] };
    } finally {
        loading.value = false;
    }
}

async function handleStep2() {
    loading.value = true;
    errors.value = {};
    try {
        const { data } = await axios.post(route('onboarding.app'), {
            name: appName.value,
            current_url: appUrl.value,
        });
        appSlug.value = data.app.slug;
        appCurrentUrl.value = data.app.current_url;
        step.value = 3;
    } catch (e) {
        errors.value = e.response?.data?.errors ?? { name: ['Something went wrong. Please try again.'] };
    } finally {
        loading.value = false;
    }
}

function handleStep3() {
    router.post(route('onboarding.run'), {
        app_slug: appSlug.value,
        goal: goal.value,
        start_url: appCurrentUrl.value,
        mode: mode.value,
        provider: provider.value,
        model: model.value,
        max_steps: props.maxSteps,
    });
}

function goToApp() {
    router.visit(route('victory-games.apps.show', appSlug.value));
}

function skip() {
    router.post(route('onboarding.skip'));
}

// ── UI helpers ────────────────────────────────────────────────────────────────
const stepLabels = ['Your profile', 'Your app', 'First test'];

function stepState(n) {
    if (n < step.value) return 'done';
    if (n === step.value) return 'active';
    return 'pending';
}
</script>

<template>
    <Head title="Get started" />

    <div class="min-h-screen bg-theme-page flex flex-col items-center justify-center px-4 py-12">

        <!-- Logo -->
        <Link href="/" class="mb-8">
            <ApplicationLogo class="h-14 w-14 fill-current text-theme-text-faint" />
        </Link>

        <!-- Card -->
        <div class="w-full max-w-lg bg-theme-card rounded-2xl shadow-xl overflow-hidden">

            <!-- Progress bar -->
            <div class="flex">
                <div
                    v-for="n in 3"
                    :key="n"
                    :class="[
                        'flex-1 h-1 transition-colors duration-300',
                        stepState(n) === 'done'   ? 'bg-green-500' :
                        stepState(n) === 'active'  ? 'bg-theme-accent' :
                                                     'bg-theme-border',
                    ]"
                />
            </div>

            <!-- Step labels -->
            <div class="flex justify-between px-6 pt-4 pb-0">
                <div
                    v-for="(label, i) in stepLabels"
                    :key="i"
                    :class="[
                        'text-xs font-medium transition-colors duration-200',
                        stepState(i + 1) === 'done'   ? 'text-green-500' :
                        stepState(i + 1) === 'active'  ? 'text-theme-text-primary' :
                                                          'text-theme-text-muted',
                    ]"
                >
                    {{ label }}
                </div>
            </div>

            <!-- Body -->
            <div class="px-8 py-8">

                <!-- ── Step 1: Profile ──────────────────────────────────────── -->
                <div v-if="step === 1">
                    <h1 class="text-2xl font-bold text-theme-text-primary">Welcome to Victory Games</h1>
                    <p class="mt-2 text-sm text-theme-text-secondary">
                        Victory Games lets you run an AI agent against any website and watch it work. First, create your tester identity.
                    </p>

                    <div class="mt-6">
                        <label class="block text-sm font-medium text-theme-text-primary mb-1" for="display_name">
                            Display name
                        </label>
                        <input
                            id="display_name"
                            v-model="displayName"
                            type="text"
                            placeholder="e.g. Alex the Tester"
                            autofocus
                            @keydown.enter="handleStep1"
                            class="w-full rounded-lg border border-theme-border bg-theme-page px-4 py-2.5 text-theme-text-primary placeholder-theme-text-muted focus:outline-none focus:ring-2 focus:ring-theme-accent-ring"
                        />
                        <p v-if="errors.display_name" class="mt-1 text-xs text-red-500">
                            {{ Array.isArray(errors.display_name) ? errors.display_name[0] : errors.display_name }}
                        </p>
                        <p class="mt-1 text-xs text-theme-text-muted">
                            This is your public tester name on the leaderboard.
                        </p>
                    </div>

                    <div class="mt-8 flex items-center justify-between">
                        <button
                            type="button"
                            class="text-sm text-theme-text-muted hover:text-theme-text-secondary underline"
                            @click="skip"
                        >
                            Skip for now
                        </button>
                        <button
                            type="button"
                            :disabled="loading || !displayName.trim()"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-theme-btn-primary text-white font-semibold text-sm disabled:opacity-40 hover:opacity-90 transition"
                            @click="handleStep1"
                        >
                            <span v-if="loading">Creating…</span>
                            <span v-else>Continue</span>
                            <svg v-if="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- ── Step 2: App ──────────────────────────────────────────── -->
                <div v-else-if="step === 2">
                    <h1 class="text-2xl font-bold text-theme-text-primary">Add the app you want to test</h1>
                    <p class="mt-2 text-sm text-theme-text-secondary">
                        Give it a name and paste the URL. The AI agent will start there.
                    </p>

                    <div class="mt-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-theme-text-primary mb-1" for="app_name">
                                App name
                            </label>
                            <input
                                id="app_name"
                                v-model="appName"
                                type="text"
                                placeholder="e.g. My SaaS App"
                                autofocus
                                class="w-full rounded-lg border border-theme-border bg-theme-page px-4 py-2.5 text-theme-text-primary placeholder-theme-text-muted focus:outline-none focus:ring-2 focus:ring-theme-accent-ring"
                            />
                            <p v-if="errors.name" class="mt-1 text-xs text-red-500">
                                {{ Array.isArray(errors.name) ? errors.name[0] : errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-theme-text-primary mb-1" for="app_url">
                                URL to test
                            </label>
                            <input
                                id="app_url"
                                v-model="appUrl"
                                type="url"
                                placeholder="https://example.com"
                                class="w-full rounded-lg border border-theme-border bg-theme-page px-4 py-2.5 text-theme-text-primary placeholder-theme-text-muted focus:outline-none focus:ring-2 focus:ring-theme-accent-ring"
                            />
                            <p v-if="errors.current_url" class="mt-1 text-xs text-red-500">
                                {{ Array.isArray(errors.current_url) ? errors.current_url[0] : errors.current_url }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-8 flex items-center justify-between">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 text-sm text-theme-text-muted hover:text-theme-text-secondary"
                            @click="step = 1"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Back
                        </button>
                        <button
                            type="button"
                            :disabled="loading || !appName.trim() || !appUrl.trim()"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-theme-btn-primary text-white font-semibold text-sm disabled:opacity-40 hover:opacity-90 transition"
                            @click="handleStep2"
                        >
                            <span v-if="loading">Creating…</span>
                            <span v-else>Continue</span>
                            <svg v-if="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- ── Step 3: Test ─────────────────────────────────────────── -->
                <div v-else-if="step === 3">
                    <h1 class="text-2xl font-bold text-theme-text-primary">Launch your first test</h1>

                    <!-- No providers configured -->
                    <div v-if="!hasProviders" class="mt-4">
                        <div class="rounded-lg border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-300">
                            No AI providers are configured yet. An admin needs to add an API key (OpenAI, Anthropic, or Gemini) to the environment before tests can run.
                        </div>
                        <p class="mt-4 text-sm text-theme-text-secondary">
                            Your profile and app are ready — you can explore your app page and run tests once a provider is set up.
                        </p>
                        <div class="mt-8">
                            <button
                                type="button"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-theme-btn-primary text-white font-semibold text-sm hover:opacity-90 transition"
                                @click="goToApp"
                            >
                                Go to my app
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Providers available -->
                    <div v-else>
                        <p class="mt-2 text-sm text-theme-text-secondary">
                            Describe what the AI agent should try to accomplish on
                            <span class="font-medium text-theme-text-primary">{{ appCurrentUrl }}</span>.
                        </p>

                        <div class="mt-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-theme-text-primary mb-1" for="goal">
                                    Goal
                                </label>
                                <textarea
                                    id="goal"
                                    v-model="goal"
                                    rows="3"
                                    placeholder="e.g. Find the pricing page and identify the cheapest plan."
                                    class="w-full rounded-lg border border-theme-border bg-theme-page px-4 py-2.5 text-theme-text-primary placeholder-theme-text-muted focus:outline-none focus:ring-2 focus:ring-theme-accent-ring resize-none"
                                />
                                <p v-if="errors.goal" class="mt-1 text-xs text-red-500">
                                    {{ Array.isArray(errors.goal) ? errors.goal[0] : errors.goal }}
                                </p>
                                <p class="mt-1 text-xs text-theme-text-muted">
                                    Write it as an intent, not step-by-step instructions.
                                </p>
                            </div>

                            <!-- Mode -->
                            <div>
                                <span class="block text-sm font-medium text-theme-text-primary mb-2">Mode</span>
                                <div class="flex gap-3">
                                    <button
                                        v-for="m in ['desktop', 'mobile']"
                                        :key="m"
                                        type="button"
                                        :class="[
                                            'flex-1 py-2 rounded-lg border text-sm font-medium transition',
                                            mode === m
                                                ? 'border-theme-accent bg-theme-accent/10 text-theme-text-primary'
                                                : 'border-theme-border text-theme-text-muted hover:border-theme-accent/50',
                                        ]"
                                        @click="mode = m"
                                    >
                                        {{ m.charAt(0).toUpperCase() + m.slice(1) }}
                                    </button>
                                </div>
                            </div>

                            <!-- Provider + Model (show only if >1 provider or always for transparency) -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-theme-text-primary mb-1" for="provider">
                                        Provider
                                    </label>
                                    <select
                                        id="provider"
                                        v-model="provider"
                                        class="w-full rounded-lg border border-theme-border bg-theme-page px-3 py-2.5 text-theme-text-primary focus:outline-none focus:ring-2 focus:ring-theme-accent-ring"
                                    >
                                        <option v-for="p in providers" :key="p.key" :value="p.key">
                                            {{ p.label }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-theme-text-primary mb-1" for="model">
                                        Model
                                    </label>
                                    <input
                                        id="model"
                                        v-model="model"
                                        type="text"
                                        class="w-full rounded-lg border border-theme-border bg-theme-page px-3 py-2.5 text-theme-text-primary focus:outline-none focus:ring-2 focus:ring-theme-accent-ring"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex items-center justify-between">
                            <div />
                            <button
                                type="button"
                                :disabled="!goal.trim() || !provider || !model.trim()"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-green-600 text-white font-semibold text-sm disabled:opacity-40 hover:bg-green-500 transition"
                                @click="handleStep3"
                            >
                                Launch test
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <ThemeToggle class="mt-6" />
    </div>
</template>
