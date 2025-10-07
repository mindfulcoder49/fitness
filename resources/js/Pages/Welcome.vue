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
        name: 'A Supportive Community',
        description: 'It would be great to have a group of supportive pro-social people who support you improving your life. I will ensure that anyone who consistently fails to make pro-social choices will not be included.',
    },
    {
        name: 'Your Issues Don\'t Define You',
        description: 'Many people have mental health issues or trauma. I am not afraid of your issues, whatever they are. Your conditions don\'t define you, your choices and your words do.',
    },
    {
        name: 'Safe and Lawful',
        description: 'The entire point of this cult is to help its members live normal, healthy, pro-social bisexual lives while increasing their physical fitness, and all activities will lie well within the law.',
    },
    {
        name: 'All Beliefs Welcome',
        description: 'This cult is built around our shared experience of being in a human body. The various paths allow for differing metaphysics and mental experiences even while we share the human physical experience.',
    },
];

const timeline = [
    {
        name: 'Earth Ritual 🌱',
        description: 'Go for a walk, bury your fears in the earth, and share a celebratory meal. A first step on our journey to a strong cult community.',
        location: 'Arnold Arboretum',
    },
    {
        name: 'Water Ritual 🌊',
        description: 'Submerge your fears into the ocean and pick your personal path alignment: Earth, Air, Fire, or Water.',
        location: 'A beach',
    },
    {
        name: 'Air Ritual 🌬️',
        description: 'Symbolically cross over obstacles on a slackline and work with your path group to create a sequence of movements representing your fitness objectives.',
        location: 'A local hilltop',
    },
    {
        name: 'Fire Ritual 🔥',
        description: 'Burn away what holds you back in a fire-pit, reveal your past fears to your new community, and merge your path\'s movements into a unified performance.',
        location: 'Fire pit',
    },
    {
        name: 'Spirit Party ✨',
        description: 'Your initiation. Put on a mask representing your new self and perform as a new member of our community dedicated to physical fitness.',
        location: 'TBA',
    },
];

const faqs = [
    {
        question: 'Why is this bisexual?',
        answer: 'Movement and physical fitness is incredibly gendered. By requiring all members to be willing to at least say they are bisexual, we create an environment that allows for easily crossing between those variously gendered physical positions and movements. You will never have to prove your bisexuality.',
    },
    {
        question: 'Why is this a cult?',
        answer: 'It\'s a cult because I am completely in charge and there will be no elections or democratized governance.',
    },
    {
        question: 'Will there be sex parties or orgies?',
        answer: 'No. I expect members will have sex with each other, possibly even in groups. You and I might not be included. We will just have to deal with that. Constantly hitting on people who are not into you will get you uninvited.',
    },
    {
        question: 'Can I have another religion and be in this cult?',
        answer: 'Yes. This cult is built around our shared experience of waking up one day to find we are in a human body and must deal with it. The various paths allow for differing metaphysics and mental experiences.',
    },
];
</script>

<template>
    <Head title="Welcome to the Bisexual Fitness Cult" />
    <div class="bg-gray-900 text-white">
        <!-- Header -->
        <header class="absolute inset-x-0 top-0 z-50">
            <nav class="flex items-center justify-between p-6 lg:px-8" aria-label="Global">
                <div class="flex lg:flex-1">
                    <Link href="/" class="-m-1.5 p-1.5">
                        <span class="sr-only">Bisexual Fitness Cult</span>
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
                            <h1 class="text-4xl font-bold tracking-tight sm:text-6xl">Unleash Your Potential. Find Your Tribe.</h1>
                            <p class="mt-6 text-lg leading-8 text-gray-300">A pro-social community dedicated to physical fitness and personal growth, outside the norms. This is for lonely bisexual people who feel beaten down and want the protection of a reliable cult of fitness enthusiasts.</p>
                            <div class="mt-10 flex items-center justify-center gap-x-6">
                                <Link v-if="canRegister" :href="route('register')" class="rounded-md bg-indigo-500 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500">Get started</Link>
                                <a href="#why-us" class="text-sm font-semibold leading-6">Learn more <span aria-hidden="true">→</span></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature section -->
            <div id="why-us" class="mx-auto mt-32 max-w-7xl px-6 sm:mt-56 lg:px-8">
                <div class="mx-auto max-w-2xl lg:text-center">
                    <h2 class="text-base font-semibold leading-7 text-indigo-400">Everything You Need</h2>
                    <p class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Why Choose This Cult?</p>
                    <p class="mt-6 text-lg leading-8 text-gray-300">Because you need a stable, supportive bisexual fitness community as a bulwark against the adversarial nature of modern society.</p>
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
            <div class="mt-32 sm:mt-56 py-24 sm:py-32 bg-white/5">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl lg:text-center">
                        <h2 class="text-base font-semibold leading-7 text-indigo-400">The Journey</h2>
                        <p class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">How It Works</p>
                        <p class="mt-6 text-lg leading-8 text-gray-300">From online engagement to in-person rituals, here is the path to initiation.</p>
                    </div>
                    <div class="mx-auto mt-16 max-w-5xl">
                        <ol class="relative border-l border-gray-700">
                            <li v-for="item in timeline" :key="item.name" class="mb-10 ml-6">
                                <span class="absolute -left-3 flex h-6 w-6 items-center justify-center rounded-full bg-indigo-900 ring-8 ring-gray-900">
                                    <svg class="h-3 w-3 text-indigo-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path></svg>
                                </span>
                                <h3 class="mb-1 flex items-center text-lg font-semibold">
                                    {{ item.name }}
                                    <span class="mr-2 ml-3 rounded bg-indigo-900 px-2.5 py-0.5 text-sm font-medium text-indigo-300">{{ item.location }}</span>
                                </h3>
                                <p class="text-base font-normal text-gray-400">{{ item.description }}</p>
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
                    <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Ready to dive in?</h2>
                    <p class="mx-auto mt-6 max-w-xl text-lg leading-8 text-gray-300">Join a community that values strength, authenticity, and personal growth. Start your journey today.</p>
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
                        <p class="text-sm leading-6 text-gray-300">A pro-social cult of fitness enthusiasts for the AI social apocalypse.</p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
