<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});

const features = [
    {
        name: 'Structured Socializing',
        description: 'Daily tasks guide members to interact around a shared interest, building the connections needed for everyone to finally meet up.',
    },
    {
        name: 'Gamified Engagement',
        description: 'Earn points for posting and interacting. A group leaderboard tracks participation and helps identify the most engaged members.',
    },
    {
        name: 'From Online to IRL',
        description: 'The platform is designed to bridge the gap between initial interest and real-life meetups, overcoming the anxiety of new social situations.',
    },
    {
        name: 'Simple Group Management',
        description: 'It\'s easy for organizers to create and run their own groups. Set a daily task, manage members, and watch your community grow.',
    },
];

const howItWorks = [
    {
        name: '1. Find or Create a Group',
        description: 'Browse public groups on the dashboard or start your own. Each group is a unique community with its own goals and activities.',
    },
    {
        name: '2. Participate in Daily Tasks',
        description: 'Every group has a daily task set by the admin. Complete the task and share your update to engage with the community.',
    },
    {
        name: '3. Earn Points & Climb the Leaderboard',
        description: 'Get points for posting your task updates, and for liking and commenting on others\' posts. See how you stack up on the group leaderboard.',
    },
    {
        name: '4. Connect and Meet Up',
        description: 'Consistent interaction builds social cohesion. Once enough members are engaged, admins can organize meetups with people who are ready to connect.',
    },
];

const faqs = [
    {
        question: 'How is this different from Meetup or Discord?',
        answer: 'This platform provides a unique structure. Daily tasks create a consistent, low-stakes reason to interact, building strong community bonds before a real-life event ever happens. It\'s about creating social cohesion first.',
    },
    {
        question: 'Is it free?',
        answer: 'Yes. For the near future, this platform will be free for both organizers and participants. It may be supported by donations eventually, but the goal is to keep it accessible.',
    },
    {
        question: 'How do I start my own group?',
        answer: 'After registering, click "Create Group" on your dashboard. Your group will be reviewed to ensure it\'s a legitimate, pro-social community. Once approved, you\'ll get a short training on how to be a successful group admin.',
    },
    {
        question: 'What kind of groups can I create?',
        answer: 'Any group with a pro-social goal is welcome. From fitness and hobbies to social clubs and support groups, if you want to bring people together around a shared activity, you can build a community here.',
    },
];
</script>

<template>
    <Head title="Welcome to AI Survival Magazine" />
    <div class="bg-gray-900 text-white">
        <!-- Header -->
        <header class="absolute inset-x-0 top-0 z-50">
            <nav class="flex items-center justify-between p-6 lg:px-8" aria-label="Global">
                <div class="flex lg:flex-1">
                    <Link href="/" class="-m-1.5 p-1.5">
                        <span class="sr-only">AI Survival Magazine</span>
                        <ApplicationLogo class="h-12 w-auto" />
                    </Link>
                </div>
                <div v-if="canLogin" class="flex flex-1 justify-end gap-x-6">
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="text-sm font-semibold leading-6">
                        Dashboard <span aria-hidden="true">&rarr;</span>
                    </Link>
                    <template v-else>
                        <Link :href="route('login')" class="text-sm font-semibold leading-6">
                            Log in <span aria-hidden="true">&rarr;</span>
                        </Link>
                        <Link v-if="canRegister" :href="route('register')" class="rounded-md bg-indigo-500 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500">
                            Register
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <main class="isolate">
            <!-- Hero section -->
            <div class="relative pt-14">
                <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80" aria-hidden="true">
                    <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-30 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]" style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)" />
                </div>
                <div class="py-24 sm:py-32">
                    <div class="mx-auto max-w-7xl px-6 lg:px-8">
                        <div class="mx-auto max-w-2xl text-center">
                            <h1 class="text-4xl font-bold tracking-tight sm:text-6xl">Build Your Community. Go From Online to IRL.</h1>
                            <p class="mt-6 text-lg leading-8 text-gray-300">A platform designed to turn shared interests into real-life connections. We provide the structure to help you build a thriving social group and motivate members to show up.</p>
                            <div class="mt-10 flex items-center justify-center gap-x-6">
                                <Link v-if="canRegister" :href="route('register')" class="rounded-md bg-indigo-500 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500">Get started</Link>
                                <a href="#how-it-works" class="text-sm font-semibold leading-6">Learn more <span aria-hidden="true">→</span></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature section -->
            <div id="why-us" class="mx-auto mt-32 max-w-7xl px-6 sm:mt-56 lg:px-8">
                <div class="mx-auto max-w-2xl lg:text-center">
                    <h2 class="text-base font-semibold leading-7 text-indigo-400">A Better Way to Connect</h2>
                    <p class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Why This Platform Works</p>
                    <p class="mt-6 text-lg leading-8 text-gray-300">We solve the hardest part of building a community: creating the social cohesion needed to overcome the anxiety and inertia of doing new things with new people.</p>
                </div>
                <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-4xl">
                    <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-10 lg:max-w-none lg:grid-cols-2 lg:gap-y-16">
                        <div v-for="feature in features" :key="feature.name" class="relative pl-16">
                            <dt class="text-base font-semibold leading-7">
                                <div class="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-500">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </div>
                                {{ feature.name }}
                            </dt>
                            <dd class="mt-2 text-base leading-7 text-gray-300">{{ feature.description }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- How it works -->
            <div id="how-it-works" class="mt-32 sm:mt-56 py-24 sm:py-32 bg-white/5">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:text-center">
                        <h2 class="text-base font-semibold leading-7 text-indigo-400">The Path to Connection</h2>
                        <p class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">How It Works</p>
                        <p class="mt-6 text-lg leading-8 text-gray-300">A simple, four-step process to build an active and engaged community.</p>
                    </div>
                    <div class="mx-auto mt-16 max-w-5xl">
                        <ol class="space-y-10">
                            <li v-for="item in howItWorks" :key="item.name" class="relative flex flex-col items-start">
                                <h3 class="text-lg font-semibold">
                                    {{ item.name }}
                                </h3>
                                <p class="mt-1 text-base font-normal text-gray-400">{{ item.description }}</p>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- FAQ -->
            <div class="mx-auto mt-32 max-w-7xl px-6 sm:mt-56 lg:px-8">
                <div class="mx-auto max-w-4xl divide-y divide-white/10">
                    <h2 class="text-2xl font-bold leading-10 tracking-tight">Frequently asked questions</h2>
                    <dl class="mt-10 space-y-8 divide-y divide-white/10">
                        <div v-for="faq in faqs" :key="faq.question" class="pt-8">
                            <dt>
                                <div class="flex w-full items-start justify-between text-left">
                                    <span class="text-base font-semibold leading-7">{{ faq.question }}</span>
                                </div>
                            </dt>
                            <dd class="mt-2 pr-12">
                                <p class="text-base leading-7 text-gray-300">{{ faq.answer }}</p>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- CTA section -->
            <div class="relative -z-10 mt-32 px-6 py-24 sm:px-16 sm:py-32 lg:px-8">
                 <div class="absolute inset-x-0 top-[-10rem] -z-10 transform-gpu overflow-hidden blur-3xl" aria-hidden="true">
                    <div class="relative left-1/2 -z-10 aspect-[1155/678] w-[36.125rem] max-w-none -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-30 sm:left-[calc(50%-40rem)] sm:w-[72.1875rem]" style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)" />
                </div>
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Ready to build your tribe?</h2>
                    <p class="mx-auto mt-6 max-w-xl text-lg leading-8 text-gray-300">Join an existing group or start your own. Your community is waiting.</p>
                    <div class="mt-10 flex items-center justify-center gap-x-6">
                         <Link v-if="canRegister" :href="route('register')" class="rounded-md bg-indigo-500 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500">Register Now</Link>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="mt-32 bg-gray-900 sm:mt-56" aria-labelledby="footer-heading">
            <h2 id="footer-heading" class="sr-only">Footer</h2>
            <div class="mx-auto max-w-7xl px-6 py-16 sm:py-24 lg:px-8 lg:py-32">
                <div class="xl:grid xl:grid-cols-3 xl:gap-8">
                    <div class="space-y-8">
                        <ApplicationLogo class="h-16 w-auto" />
                        <p class="text-sm leading-6 text-gray-300">A platform for building pro-social communities that bridge the online-offline gap.</p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
