<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const activeSection = ref('overview');

const sections = [
    { id: 'overview',         label: 'Overview' },
    { id: 'victor-profiles',  label: 'Victor Profiles' },
    { id: 'apps',             label: 'Apps' },
    { id: 'native-runs',      label: 'Native AIUX Runs' },
    { id: 'competitions',     label: 'Competitions' },
    { id: 'groups',           label: 'Groups' },
];

function scrollTo(id) {
    const el = document.getElementById(id);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        activeSection.value = id;
    }
}

let observer;

onMounted(() => {
    const targets = sections.map(s => document.getElementById(s.id)).filter(Boolean);
    observer = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    activeSection.value = entry.target.id;
                    break;
                }
            }
        },
        { rootMargin: '-20% 0px -70% 0px', threshold: 0 }
    );
    targets.forEach(t => observer.observe(t));
});

onUnmounted(() => {
    observer?.disconnect();
});
</script>

<template>
    <Head title="Help — VibeCode International" />

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">

        <!-- Page header -->
        <div class="mb-10">
            <h1 class="text-3xl font-extrabold text-theme-text-primary">Help &amp; Documentation</h1>
            <p class="mt-2 text-theme-text-secondary">Everything you need to know about VibeCode International.</p>
        </div>

        <div class="flex gap-10">

            <!-- Sidebar nav (sticky) -->
            <aside class="hidden lg:block w-52 shrink-0">
                <nav class="sticky top-24 space-y-1">
                    <button
                        v-for="s in sections"
                        :key="s.id"
                        @click="scrollTo(s.id)"
                        class="w-full text-left px-3 py-2 rounded-lg text-sm transition"
                        :class="activeSection === s.id
                            ? 'bg-theme-elevated text-theme-text-primary font-semibold'
                            : 'text-theme-text-muted hover:text-theme-text-primary hover:bg-theme-elevated'"
                    >
                        {{ s.label }}
                    </button>
                </nav>
            </aside>

            <!-- Mobile section jump -->
            <div class="lg:hidden mb-6 w-full">
                <select
                    @change="scrollTo($event.target.value)"
                    class="w-full rounded-lg border border-theme-border bg-theme-input text-theme-text-primary px-3 py-2 text-sm"
                >
                    <option value="">Jump to section&hellip;</option>
                    <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.label }}</option>
                </select>
            </div>

            <!-- Main content -->
            <main class="flex-1 min-w-0 space-y-16">

                <!-- OVERVIEW -->
                <section id="overview">
                    <h2 class="text-2xl font-bold text-theme-text-primary mb-1">Overview</h2>
                    <p class="text-sm text-theme-text-muted mb-6">What VibeCode International is and how it fits together.</p>

                    <div class="prose-help">
                        <p>VibeCode International is a platform for competitive AI-driven UX testing. It has two main pillars:</p>

                        <ul>
                            <li><strong>Victory Games</strong> — AI browser agents (AIUX runs) test real web apps and compete in judged brackets. Winners earn placements and certificates.</li>
                            <li><strong>Groups</strong> — Community spaces where members can post, chat, track availability, schedule meetups, and work toward shared goals.</li>
                        </ul>

                        <p>To participate in Victory Games you need a <strong>Victor profile</strong> (your competitive identity) and at least one <strong>App</strong> (the web app being tested). From an app page you can queue <strong>Native AIUX runs</strong>, which dispatch AI browser sessions that execute a goal you describe and report back detailed step-by-step results.</p>

                        <h3>Quick-start checklist</h3>
                        <ol>
                            <li>Create a Victor profile from the <strong>Victory Games</strong> nav link.</li>
                            <li>Create an App and enter its URL.</li>
                            <li>Queue a Native AIUX run with a goal description.</li>
                            <li>Watch the run execute and review the step-by-step postmortem.</li>
                            <li>Optionally enter a Competition to have your run judged against others.</li>
                        </ol>
                    </div>
                </section>

                <!-- VICTOR PROFILES -->
                <section id="victor-profiles">
                    <h2 class="text-2xl font-bold text-theme-text-primary mb-1">Victor Profiles</h2>
                    <p class="text-sm text-theme-text-muted mb-6">Your competitive identity in Victory Games.</p>

                    <div class="prose-help">
                        <p>A Victor profile is a public page that represents you (or your team) in competitions. It tracks your run history, placements, apps, and certificates all in one place.</p>

                        <h3>Creating a Victor profile</h3>
                        <ol>
                            <li>Go to <strong>Victory Games</strong> in the top nav.</li>
                            <li>Click <strong>Meet the Victors</strong>, then <strong>Create Victor Profile</strong>.</li>
                            <li>Choose a display name and a unique URL slug (e.g. <code>your-handle</code>).</li>
                            <li>Add an optional bio, avatar, and social links (GitHub, website, Twitter/X).</li>
                            <li>Save. Your public profile is live at <code>/victory-games/victors/your-handle</code>.</li>
                        </ol>

                        <h3>Editing your profile</h3>
                        <p>Visit your Victor profile page and click <strong>Edit</strong>. You can update your display name, bio, avatar image, and social URLs at any time. Changes are reflected immediately on your public page.</p>

                        <h3>Accessing your Victor from the nav</h3>
                        <p>Once you have a Victor profile, a <strong>My Victor</strong> link appears in the top navigation for quick access.</p>

                        <h3>Claiming an external Victor</h3>
                        <p>If an admin has pre-created a Victor on your behalf (e.g. from a competition import), you can claim it using a token. Go to your Victor's page and use the claim flow — this links the Victor to your account so you can manage it.</p>
                    </div>
                </section>

                <!-- APPS -->
                <section id="apps">
                    <h2 class="text-2xl font-bold text-theme-text-primary mb-1">Apps</h2>
                    <p class="text-sm text-theme-text-muted mb-6">The web apps that AI agents test against.</p>

                    <div class="prose-help">
                        <p>An App represents a live web application you want to test. It holds the URL your agent navigates to, a description, a team of Victor members, and the full history of all runs made against it.</p>

                        <h3>Creating an App</h3>
                        <ol>
                            <li>You must have a Victor profile first.</li>
                            <li>From your Victor's profile page, click <strong>Create App</strong>.</li>
                            <li>Enter a name, the app's URL, and an optional description.</li>
                            <li>Save. Your app page is live at <code>/victory-games/apps/your-app-slug</code>.</li>
                        </ol>

                        <h3>Editing an App</h3>
                        <p>Visit the App page and click <strong>Edit App</strong> (visible only to team members with edit access). You can update the name, description, and current URL.</p>

                        <h3>Managing team members</h3>
                        <p>Apps support multiple Victor team members. From the App page:</p>
                        <ul>
                            <li>Click <strong>+ Add member</strong> and enter a Victor's slug to invite them.</li>
                            <li>Click the <strong>✕</strong> next to a member to remove them.</li>
                            <li>The Victor who created the App is the owner; all team members can queue runs.</li>
                        </ul>

                        <h3>Run history</h3>
                        <p>Every AIUX run against the App is listed on the App page under <strong>Run History</strong>. You can click <strong>View run →</strong> to see full step details, or <strong>Reuse config</strong> to pre-fill the run form with the same settings.</p>
                    </div>
                </section>

                <!-- NATIVE RUNS -->
                <section id="native-runs">
                    <h2 class="text-2xl font-bold text-theme-text-primary mb-1">Native AIUX Runs</h2>
                    <p class="text-sm text-theme-text-muted mb-6">AI browser agents that test your app and report step-by-step results.</p>

                    <div class="prose-help">
                        <p>A Native AIUX run dispatches a real browser session powered by Playwright and an LLM. The agent navigates your app, takes actions toward a stated goal, and records every step — including screenshots, intent, reasoning, and outcome — for later review.</p>

                        <h3>Queueing a run</h3>
                        <ol>
                            <li>Open your App page.</li>
                            <li>Click <strong>New native run</strong> in the Native AIUX Testing section.</li>
                            <li>Fill in the run form (details below).</li>
                            <li>Click <strong>Queue Native Run</strong>. The run is dispatched to a background queue worker.</li>
                            <li>You'll be taken to the run detail page where you can watch it progress live.</li>
                        </ol>

                        <h3>Run form fields</h3>

                        <div class="overflow-x-auto">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Goal</strong></td>
                                        <td>Plain-language description of what the agent should accomplish. Be specific — "Find the pricing page and identify the cheapest plan" produces better results than "explore the site".</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Start URL</strong></td>
                                        <td>Where the browser opens. Defaults to the App's current URL. Override this to start the agent on a specific page.</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Mode</strong></td>
                                        <td><code>Desktop</code> uses a full-width viewport; <code>Mobile</code> emulates a narrow mobile screen. Test both to surface responsive-design issues.</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Provider</strong></td>
                                        <td>The AI provider that drives the agent (e.g. Anthropic, OpenAI). Providers available depend on which API keys are configured in the environment.</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Model</strong></td>
                                        <td>The specific model to use within the chosen provider (e.g. <code>claude-sonnet-4-5</code>). More capable models produce better reasoning but use more budget.</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Max steps</strong></td>
                                        <td>Upper bound on how many planning/execution steps the agent may take. A typical exploratory run uses 8–15 steps. Complex goals may need more.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <h3>Run statuses</h3>

                        <div class="overflow-x-auto">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Meaning</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><span class="status-badge status-queued">queued</span></td><td>Waiting for a queue worker to pick it up.</td></tr>
                                    <tr><td><span class="status-badge status-running">running</span></td><td>The browser is open and the agent is executing steps.</td></tr>
                                    <tr><td><span class="status-badge status-analyzing">analyzing</span></td><td>Steps are complete; the LLM is writing the postmortem analysis.</td></tr>
                                    <tr><td><span class="status-badge status-completed">completed</span></td><td>Run finished successfully. Full step log and analysis are available.</td></tr>
                                    <tr><td><span class="status-badge status-failed">failed</span></td><td>An unrecoverable error stopped the run. Check the step log for details.</td></tr>
                                    <tr><td><span class="status-badge status-stopped">stopped</span></td><td>You manually stopped the run before it finished.</td></tr>
                                    <tr><td><span class="status-badge status-loop">loop detected</span></td><td>The agent repeated the same action too many times and was halted automatically.</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <h3>Reading the run detail page</h3>
                        <p>Each run has a detail page accessible from the run history or via a direct link. It shows:</p>
                        <ul>
                            <li><strong>Steps</strong> — Each step shows the action taken (<code>navigate</code> or <code>execute_js</code>), the agent's intent and reasoning, a screenshot of the page, and whether the action succeeded.</li>
                            <li><strong>Postmortem</strong> — After a completed run an LLM writes a structured analysis: what worked, what failed, and UX recommendations.</li>
                            <li><strong>Metadata</strong> — Provider, model, mode, step count, start/end time.</li>
                        </ul>

                        <h3>Stopping a run</h3>
                        <p>If a run is in the <code>running</code> state you can click <strong>Stop run</strong> on the detail page. The browser session ends and the run is marked <code>stopped</code>.</p>

                        <h3>Reusing a run configuration</h3>
                        <p>On the App page, click <strong>Reuse config</strong> next to any historical run to pre-fill the run form with the same goal, URL, mode, provider, model, and step limit. Useful for re-testing after fixing an issue.</p>
                    </div>
                </section>

                <!-- COMPETITIONS -->
                <section id="competitions">
                    <h2 class="text-2xl font-bold text-theme-text-primary mb-1">Competitions</h2>
                    <p class="text-sm text-theme-text-muted mb-6">Judged brackets where apps go head-to-head.</p>

                    <div class="prose-help">
                        <p>Competitions are organized events where Victor entries are ranked by an LLM judge on real usability outcomes. Each competition has a name, description, and a list of entries (runs). Results are shown on the Competition page and winning Victors receive certificates.</p>

                        <h3>How judging works</h3>
                        <p>Each competition entry is a run (native or imported) submitted by a Victor. The judge evaluates runs on criteria defined for that competition — typically task completion, navigation efficiency, and UX quality. Runs are ranked and assigned placements (🥇 1st, 🥈 2nd, 🥉 3rd).</p>

                        <h3>Entering a competition</h3>
                        <p>Competition entry is typically managed by an admin. If a competition is accepting entries, your Victor profile and App must be set up. Admins may send welcome emails with entry instructions. Once entered, your run result is associated with the competition.</p>

                        <h3>Placements &amp; certificates</h3>
                        <ul>
                            <li>Top-placed Victors earn a <strong>placement badge</strong> (🥇/🥈/🥉) shown on their profile and the competition page.</li>
                            <li>A downloadable <strong>certificate</strong> is generated for each placement. Certificates are accessible from <strong>My Certificates</strong> on the Victory Games page.</li>
                            <li>Certificates include a unique tokenized download link — you can share or archive them without requiring login.</li>
                        </ul>

                        <h3>Viewing competitions</h3>
                        <p>All competitions are listed on the <strong>Victory Games</strong> homepage. Click a competition card to see its bracket, entries, champion, and results.</p>
                    </div>
                </section>

                <!-- GROUPS -->
                <section id="groups">
                    <h2 class="text-2xl font-bold text-theme-text-primary mb-1">Groups</h2>
                    <p class="text-sm text-theme-text-muted mb-6">Community spaces for collaboration and accountability.</p>

                    <div class="prose-help">
                        <p>Groups are member communities where you can post updates, chat, coordinate schedules, track shared goals, and hold meetups. They are separate from Victory Games but live on the same platform.</p>

                        <h3>Finding and joining a Group</h3>
                        <ol>
                            <li>Click <strong>Groups</strong> in the top nav (visible when Groups are enabled).</li>
                            <li>Browse the list of public groups. Click a group card to see its public preview.</li>
                            <li>Click <strong>Request to join</strong> or <strong>Join</strong> depending on the group's settings.</li>
                            <li>Once approved you'll have full access to the group's feed, chat, and features.</li>
                        </ol>

                        <h3>Group feed (posts)</h3>
                        <p>The main group page shows a feed of member posts. You can:</p>
                        <ul>
                            <li>Write a post (text + optional media) in the composer at the top.</li>
                            <li>Like posts and leave comments.</li>
                            <li>Admins can feature a post to pin it at the top of the feed.</li>
                        </ul>

                        <h3>Group chat</h3>
                        <p>Each group has a real-time chat room. Click <strong>Chat</strong> from the group sidebar to open it. Messages are visible to all members. Unread message counts are shown in the group header.</p>

                        <h3>Availability</h3>
                        <p>The <strong>Availability</strong> tab lets members share what times they are free during the week. Fill in your available time blocks and the view aggregates everyone's availability so the group can identify times that work for all or most members.</p>

                        <h3>Meetups</h3>
                        <p>Group admins can schedule meetups with a date, time, and description. Members can RSVP. Upcoming meetups appear in the group sidebar and on the Meetups tab.</p>

                        <h3>Tasks &amp; goals</h3>
                        <p>Admins can create tasks (shared group challenges or goals) and set one as the current focus. Members' progress toward the current task contributes to the leaderboard. Tasks help align the group around a common objective for a given period.</p>

                        <h3>Leaderboard</h3>
                        <p>The group leaderboard ranks members by their contribution points (posts, completions, engagement). It's shown in the group sidebar and updates as members participate.</p>

                        <h3>Group blog</h3>
                        <p>Selected posts can be promoted to the group blog — a curated long-form feed separate from the main activity stream. Admins control which posts appear in the blog.</p>

                        <h3>Inviting others</h3>
                        <p>Group members can copy an invitation link from the group page (the link includes a <code>?group=</code> parameter). Anyone who registers using that link is automatically associated with the group for the join flow.</p>
                    </div>
                </section>

            </main>
        </div>
    </div>
</template>

<style scoped>
/* Prose-like styles scoped to help content */
.prose-help {
    @apply text-theme-text-secondary text-sm leading-relaxed space-y-4;
}

.prose-help p {
    @apply text-theme-text-secondary;
}

.prose-help h3 {
    @apply text-base font-semibold text-theme-text-primary mt-6 mb-2;
}

.prose-help ul {
    @apply list-disc pl-5 space-y-1;
}

.prose-help ol {
    @apply list-decimal pl-5 space-y-1;
}

.prose-help li {
    @apply text-theme-text-secondary;
}

.prose-help strong {
    @apply text-theme-text-primary font-semibold;
}

.prose-help code {
    @apply bg-theme-elevated text-theme-text-primary rounded px-1 py-0.5 text-xs font-mono;
}

.prose-help table {
    @apply w-full text-sm border-collapse;
}

.prose-help th {
    @apply text-left text-xs font-semibold text-theme-text-muted uppercase tracking-wide border-b border-theme-border pb-2 pr-4;
}

.prose-help td {
    @apply py-2 pr-4 border-b border-theme-border align-top text-theme-text-secondary;
}

.status-badge {
    @apply inline-block rounded-full px-2 py-0.5 text-xs font-medium;
}

.status-queued   { @apply bg-slate-500/10 text-slate-400; }
.status-running  { @apply bg-blue-500/10 text-blue-400; }
.status-analyzing { @apply bg-amber-500/10 text-amber-400; }
.status-completed { @apply bg-green-500/10 text-green-400; }
.status-failed   { @apply bg-red-500/10 text-red-400; }
.status-stopped  { @apply bg-zinc-500/10 text-zinc-400; }
.status-loop     { @apply bg-orange-500/10 text-orange-400; }
</style>
