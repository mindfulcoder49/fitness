<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Contributor;
use App\Models\Group;
use App\Models\Section;
use Illuminate\Database\Seeder;

class VibeCodeOlympicsSeeder extends Seeder
{
    public function run(): void
    {
        // ── Contributor ────────────────────────────────────────────────────────
        $staff = Contributor::firstOrCreate(
            ['slug' => 'vibecode-staff'],
            [
                'name'    => 'VibeCode Staff',
                'bio'     => 'Editorial content from the VibeCode International team.',
                'user_id' => 1,
            ]
        );

        // ── Sections ───────────────────────────────────────────────────────────
        $announcements = Section::firstOrCreate(
            ['slug' => 'announcements'],
            ['name' => 'Announcements', 'sort_order' => 1, 'is_active' => true]
        );

        $victoryGames = Section::firstOrCreate(
            ['slug' => 'victory-games'],
            ['name' => 'Victory Games', 'sort_order' => 2, 'is_active' => true]
        );

        // ── Groups ─────────────────────────────────────────────────────────────
        $groups = [
            [
                'name'        => 'Plan the Next VibeCode Victory Games',
                'description' => 'Help shape the next competition — format, timing, rules, judging criteria, and anything else that makes it better. Open to anyone who wants to be involved. Next event: May 26th at the NERD Center.',
                'is_public'   => true,
                'creator_id'  => 1,
            ],
            [
                'name'        => 'VibeCode Magazine Writers',
                'description' => 'For contributors who want to write for the VibeCode International magazine — competition recaps, entry profiles, technical deep-dives, and community pieces.',
                'is_public'   => true,
                'creator_id'  => 1,
            ],
            [
                'name'        => 'VibeCode Victors',
                'description' => 'A public group for past competition entrants and victors. Share what you\'ve built, get feedback, and stay connected with the community.',
                'is_public'   => true,
                'creator_id'  => 1,
            ],
        ];

        foreach ($groups as $groupData) {
            Group::firstOrCreate(['name' => $groupData['name']], $groupData);
        }

        // ── Articles ───────────────────────────────────────────────────────────
        $publishedAt = now()->subDays(1);

        $articles = [

            // ── Article 1: Announcement ──────────────────────────────────────
            [
                'section'     => $announcements,
                'title'       => 'Welcome to VibeCode International',
                'slug'        => 'welcome-to-vibecode-international',
                'excerpt'     => 'We built a platform for vibe-coded apps to compete, get tested by AI agents, and earn a public record of what they can do. Here\'s what that means and why we think it matters.',
                'is_featured' => true,
                'content'     => <<<HTML
<ul>
  <li>New platform for vibe-coded apps to compete in AI-judged UX tournaments</li>
  <li>AI agents test submitted apps; AI judge scores brackets; results are public</li>
  <li>First competition already complete — 25 attendees, 8 apps built in two hours, one winner still in high school</li>
  <li>Includes a magazine, public community groups, and a growing record of AI-tested apps</li>
  <li>Next event: May 26th at the NERD Center</li>
  <li>"Olympics" is very trademarked; we learned this the hard way; we are sorry</li>
</ul>

<p>There's a new kind of software being built right now. It's fast, it's ambitious, and a lot of it is being shipped by people who are moving faster than the traditional software industry is comfortable with. We're calling it vibe-coded, and we think it deserves its own arena.</p>

<p>VibeCode International is that arena.</p>

<h2>What We Do</h2>

<p>We run AI-judged UX competitions called the VibeCode Victory Games. Builders submit their apps. AI agents — the same kind of autonomous browser agents that are increasingly being used for real-world testing — navigate those apps, try to accomplish meaningful tasks, and report what they find. An AI judge evaluates the results across brackets and determines a winner.</p>

<p>The competitions are real. The test runs are real. The results are public.</p>

<h2>Why AI Judges?</h2>

<p>Because AI agents are increasingly the users. Whether it's a customer service bot navigating your checkout flow, an automation tool filling out your forms, or a future agent-browser doing research on someone's behalf — the ability of your app to be legible and navigable by a non-human is already a meaningful product quality. We think it's worth measuring.</p>

<p>Human judgment matters too, and our competitions include human editorial context alongside the AI verdicts. But the baseline test — can an agent actually accomplish something on your app — is one worth running.</p>

<h2>The Magazine</h2>

<p>You're reading it. VibeCode International publishes articles covering our competitions, the entries, the builders behind them, and the broader questions that come up when you put AI agents in front of real apps and watch what happens. Every competition gets a recap. Every entry gets its own write-up. Nothing is anonymous.</p>

<h2>The Community</h2>

<p>We have public groups for victors, for people who want to write for the magazine, and for people who want to help plan the next competition. If any of those describe you, you're welcome here.</p>

<h2>A Note on the Name</h2>

<p>Our first competition was called the VibeCode Olympics. We chose that name enthusiastically and abandoned it quickly upon learning that the International Olympic Committee has extremely strong feelings about the word "Olympics" and a legal team to match. The event has been renamed the first VibeCode Victory Games. We are sorry to the IOC, to actual Olympians, and to anyone who printed anything with the old name on it.</p>

<h2>What's Next</h2>

<p>We're doing it again. The second VibeCode Victory Games is scheduled for <strong>May 26th at the NERD Center</strong>. If you want to compete, help organize, or just watch an AI agent try to use whatever you've built, we'd love to have you.</p>

<p>Welcome to VibeCode International.</p>
HTML,
            ],

            // ── Article 2: Recap ─────────────────────────────────────────────
            [
                'section'     => $victoryGames,
                'title'       => 'The First VibeCode Victory Games — Competition Recap',
                'slug'        => 'first-vibecode-victory-games-recap',
                'excerpt'     => 'Twenty-five people showed up. Eight apps were built in two hours. The AI judge worked. The winner is in high school. Here is the full story.',
                'is_featured' => true,
                'content'     => <<<HTML
<ul>
  <li>Originally called the VibeCode Olympics; renamed the Victory Games after a crash course in IOC trademark law</li>
  <li>~25 attendees; 8 apps built and submitted in two hours; every participant deployed to a live URL</li>
  <li>5 brackets, 3 rounds each, fully AI-judged — and the judge actually worked</li>
  <li>Winner Giri Koppolu is still in high school</li>
  <li>Auth walls were instant death; no-login + obvious task + visible state change won every time</li>
  <li>CraftCode was the competition's most interesting edge case: the tool worked perfectly and still lost</li>
  <li>Next event: May 26th at the NERD Center</li>
</ul>

<p>About twenty-five people showed up to vibecode. Eight apps were built from scratch and submitted to the AI-UX Tester system in the span of two hours. Every participant pushed their app to a live URL, because localhost didn't cut it at this competition. The AI judge ran five independent brackets and actually worked. A high schooler won.</p>

<p>This is the first VibeCode Victory Games, and it was better than we had any right to expect.</p>

<h2>A Note on the Name</h2>

<p>We called it the VibeCode Olympics. This was a mistake in the narrow legal sense that "Olympics" is one of the most aggressively protected trademarks in existence and we should have known that before we printed anything. The event has been renamed the VibeCode Victory Games. We apologize to the International Olympic Committee, to actual Olympic athletes, and to the spirit of international goodwill. We are a very small website. Please do not sue us.</p>

<h2>How It Worked</h2>

<p>Each of the eight submitted apps was run through five independent brackets. Each bracket was a three-round tournament: head-to-head matchups judged by an AI evaluating how successfully an autonomous browser agent could accomplish something meaningful on each app. Final standings were determined by aggregate performance across all five brackets.</p>

<h2>The Results</h2>

<p><strong>First place: Giri Koppolu</strong> with <a href="https://spendly.giri-kop-007.workers.dev/">Spendly</a>, a no-auth budgeting app. Giri won three of five brackets. Did we mention he's still in high school? He has three connections on LinkedIn right now. We suspect that number is about to change.</p>

<p><strong>Second place</strong> went to <a href="https://vibe-dusky-five.vercel.app/">QuickPlate</a>, a clean, minimal recipe site that won the other two brackets on frictionlessness alone.</p>

<p><strong>Third place</strong> went to <a href="https://hyoungseo.com/the-vibe-code-olympics-beta/advanced/">RecipeBox</a>, built by the team of Hyoungseo Son, Wonhee Lee, and HUNJUN SHIN — intimidatingly smart people who built a polished recipe experience with a working servings calculator and consistently made it deep into brackets before losing to Spendly.</p>

<h2>What Won and Why</h2>

<p>The pattern across all five brackets was consistent enough to call a rule: no login required + single obvious primary task + visible persistent state change = wins. Spendly did all three at once. An agent could land, add transactions, watch the budget update in real time, and see clear warnings when the limit was hit. The evidence was in the screenshots. The judge didn't have to take the agent's word for anything.</p>

<p>QuickPlate and RecipeBox won on the simpler version of the same logic: no auth, one clear task, clean execution. They traded depth for frictionlessness and it worked until they faced an app with more to demonstrate.</p>

<h2>What Lost and Why</h2>

<p>Apps behind authentication never cleared Round 1. This is the competition's most punishing dynamic and it hit multiple entrants. Brian Munroe's Brand Compliance Checker and Lumnia, the AI project assistant, both ran into this — though Lumnia managed to get one agent through in one bracket and showed a genuinely good product underneath.</p>

<p>Sabuhi Guliyev's <a href="https://pathlight-blond.vercel.app/">Pathlight</a> — which Sabuhi, a professional software developer, finished building fastest — went 2-5 not because it failed but because it was too simple to accumulate evidence against deeper apps. It never made an error. It just ran out of things to demonstrate.</p>

<p>Sampath Pranay Beela's <a href="https://codecraft-3gnu.onrender.com/">CraftCode</a> is the competition's most interesting result. The app worked. The agent completed the flow correctly every time. CraftCode lost because the repositories it analyzed had problems, and the AI judge interpreted the bad grades in the report as evidence of a bad app. This is a genuine rubric limitation we're fixing for next time.</p>

<p>Akshat Pandey, Landen Ingerslev, and Theodore Tibbs — undergrad freshmen, sat in the front row — built <a href="https://profound-kulfi-dc850d.netlify.app/">Road Hazard</a>, a crash reporting and road safety app. The app itself worked. They ran out of time to craft agent-friendly task flows and optimize their submission, and with more runway they could have been a real contender.</p>

<h2>Thank You</h2>

<p>None of this happens without the people who showed up, built something, and put it in front of an AI judge with two hours on the clock. Enormous thanks to every participant, and special thanks to Brian Munroe, Toni Noble, Noah Markowitz, Monica Phang CFA, Ratna Sekhar Dhanunjay Thota, and Doug Levin for making the event what it was.</p>

<p>We're doing it again. <strong>May 26th, the NERD Center.</strong> See you there.</p>
HTML,
            ],

            // ── Article 3: Spendly ───────────────────────────────────────────
            [
                'section'     => $victoryGames,
                'title'       => 'Spendly — First Place 🥇',
                'slug'        => 'spendly-first-place-vibecode-victory-games',
                'excerpt'     => 'Giri Koppolu is still in high school. He also just won the first VibeCode Victory Games with a budgeting app that gave AI agents exactly what they needed: instant actions, persistent state, and unambiguous evidence of success.',
                'is_featured' => false,
                'content'     => <<<HTML
<ul>
  <li>Built by Giri Koppolu — still in high school, three LinkedIn connections (for now)</li>
  <li>URL: spendly.giri-kop-007.workers.dev — lightweight budgeting app, no account required</li>
  <li>Won 3 of 5 brackets; finals against QuickPlate in all five</li>
  <li>Victory formula: instant transaction logging + real-time budget health + loud over-budget warnings + localStorage persistence</li>
  <li>State changes were verifiable in screenshots — judges didn't have to trust the agent's report</li>
  <li>Human gaps: local-only data, no backup or multi-device sync, color-reliant accessibility cues</li>
</ul>

<p>Giri Koppolu is still in high school. He also just won the first VibeCode Victory Games.</p>

<p>He built Spendly in two hours, deployed it to a live URL on Cloudflare Workers, submitted it to an AI testing system, and watched an autonomous agent use it well enough across five independent brackets to beat eight other apps — including work from professional developers and university teams. Congratulations, Giri. You currently have three connections on LinkedIn. We suspect that's about to change.</p>

<h2>The App</h2>

<p>Spendly is a budgeting tool. No account. No onboarding. You land on the page and you can immediately start logging transactions, setting a budget, and watching your remaining balance update in real time. When you approach your limit, the interface tells you. When you exceed it, it tells you loudly. Data persists in localStorage — no server required.</p>

<p>This is exactly what an AI agent wants to find.</p>

<h2>Why Agents Loved It</h2>

<p>An autonomous agent approaching an unknown app is looking for affordances: clear inputs, legible actions, visible results. Spendly provided all three without friction. The agent didn't need to register, verify an email, or navigate an onboarding wizard. The primary task — log transactions, track a budget — was immediately available and immediately demonstrable.</p>

<p>Equally important: the results persisted. When the agent added a transaction and checked the balance, the numbers were actually different. When enough transactions pushed past the budget limit, the warning actually appeared. A judge looking at the screenshots could verify every claimed outcome without taking anything on faith. In a competition where the judge is also an AI reading structured run logs, the ability to point at DOM state and say "see, it changed" is enormously valuable.</p>

<p>Spendly's losses — it did lose some brackets — came in head-to-heads where the judging rubric tilted toward low-friction simplicity. For a judge evaluating whether an agent "accomplished something significant," a completed budget with logged transactions is stronger than a rendered recipe page, but not always by enough to overcome a zero-error recipe app's frictionlessness.</p>

<h2>For Human Users</h2>

<p>The same qualities that won the competition make Spendly genuinely useful for quick personal budgeting. Fast to open, fast to use, honest about where your money is going.</p>

<p>The real-world gaps: localStorage means your budget lives only in your current browser. Close the tab on a different device and it's gone. No backup, no sync, no account to recover from. The interface also leans on color to communicate budget health, which creates friction for users with color vision differences. These are solvable problems and they don't change the result: Spendly was the best app in this competition.</p>

<p>Giri, build the account layer. This thing has legs.</p>
HTML,
            ],

            // ── Article 4: QuickPlate ────────────────────────────────────────
            [
                'section'     => $victoryGames,
                'title'       => 'QuickPlate — Second Place 🥈',
                'slug'        => 'quickplate-second-place-vibecode-victory-games',
                'excerpt'     => 'Silver goes to QuickPlate, a minimal recipe site that won two brackets by doing one thing perfectly: getting out of the way.',
                'is_featured' => false,
                'content'     => <<<HTML
<ul>
  <li>URL: vibe-dusky-five.vercel.app — minimal recipe site, single path from landing to complete recipe</li>
  <li>Won 2 of 5 brackets; consistently reached the goal in the fewest actions with zero errors</li>
  <li>The cleanliness was the feature — agent task was trivial, which is exactly right for a recipe lookup</li>
  <li>Lost to Spendly on interaction depth; beat everyone else on frictionlessness</li>
  <li>Human gaps: no prep/cook times, no servings scaler, no search or filter, no print/save</li>
</ul>

<p>QuickPlate didn't win the first VibeCode Victory Games, but it won two of the five brackets and finished second overall, and the competition data makes clear this was a close contest between two apps executing very different strategies equally well.</p>

<p>Where Spendly won on depth — rich persistent state, real-time feedback, multiple logged interactions — QuickPlate won on absence. There was nothing in the way. An agent landing on QuickPlate's homepage could find a recipe and read every detail in the minimum possible number of steps, with zero errors and zero friction at any point in the flow.</p>

<h2>The Minimal Approach</h2>

<p>QuickPlate is a recipe site. It does one thing: shows you a recipe. The path from landing page to complete recipe — title, ingredients, step-by-step instructions — is direct and unobstructed. No account creation, no paywall, no newsletter modal, no notification prompt. You click a dish, you get the recipe.</p>

<p>For an AI agent, this is nearly ideal. The agent's job in a VibeCode Victory Games run is to "accomplish something significant." On a recipe site, the significant thing is getting to a complete recipe. QuickPlate makes that so easy that the agent almost can't fail — and in a competition where failing means losing to an opponent that didn't fail, not failing is a very good strategy.</p>

<h2>Why It Placed Second</h2>

<p>QuickPlate's losses all came against Spendly, and they all came for the same reason: when two apps both execute flawlessly, the judge evaluates the depth of what the agent accomplished. A complete recipe page is a meaningful accomplishment. A budget with six logged transactions, a running total, and an over-budget warning is a more complex demonstrable accomplishment. Spendly won those matchups by having more to show, not by being easier to use.</p>

<p>Against any other opponent in the field, QuickPlate's frictionless path to a complete, correct result was enough to win.</p>

<h2>For Human Users</h2>

<p>The same qualities that made QuickPlate competitive in AI testing make it good for actual cooking: find what you need quickly, without distractions. For someone who just wants a recipe and not a fifteen-paragraph story about the chef's Italian grandmother, QuickPlate is the right tool.</p>

<p>The gaps will be familiar: no prep or cook time metadata, no servings calculator, no way to scale quantities, no search or filter, no print/save. These aren't edge cases — they're the features that keep a recipe site open for more than one session. QuickPlate has the foundation; depth is the next step.</p>
HTML,
            ],

            // ── Article 5: RecipeBox ─────────────────────────────────────────
            [
                'section'     => $victoryGames,
                'title'       => 'RecipeBox — Third Place 🥉',
                'slug'        => 'recipebox-third-place-vibecode-victory-games',
                'excerpt'     => 'Bronze goes to RecipeBox, built by Hyoungseo Son, Wonhee Lee, and HUNJUN SHIN — a team the competition will not forget. Their polished recipe site with a working servings calculator consistently made it deep into every bracket.',
                'is_featured' => false,
                'content'     => <<<HTML
<ul>
  <li>Built by Hyoungseo Son, Wonhee Lee, and HUNJUN SHIN — intimidatingly smart, and a team</li>
  <li>URL: hyoungseo.com/the-vive-code-olympics-beta/advanced/ — polished single-page recipe with full metadata and interactive servings calculator</li>
  <li>Key differentiator over QuickPlate: the servings calculator gave agents a visible, confirmable state change to demonstrate</li>
  <li>Consistently won early rounds across all five brackets; repeatedly lost finals to Spendly's deeper interaction loop</li>
  <li>Human gaps: narrow scope per page, no search or filter, no shopping list, no timers or personalization</li>
</ul>

<p>Third place in the first VibeCode Victory Games went to RecipeBox, and the team behind it — Hyoungseo Son, Wonhee Lee, and HUNJUN SHIN — were, by all accounts, intimidatingly smart. They built a polished, complete recipe experience as a team and consistently made it deeper into every bracket than any other entry except the top two.</p>

<p>The URL still says "olympics" in it. They're not the only ones.</p>

<h2>One Interactive Hook</h2>

<p>QuickPlate, which finished second, is also a recipe site. Also clean, also frictionless. The difference between second and third place in this competition came down to a single interactive element: RecipeBox lets you adjust the number of servings and watch the ingredient quantities update in real time.</p>

<p>This is a small feature. For a human cook it's a convenience. For an AI agent trying to "accomplish something significant," it's a gift: a visible, confirmable state change triggered by a clear user action. The agent could demonstrate that it found the recipe <em>and</em> that it used the app's interactivity. That's a stronger run than a recipe page where the only accomplishment is navigation.</p>

<p>The servings calculator didn't guarantee victory — Spendly's deeper interaction loop beat RecipeBox in every final — but it was enough to reliably beat apps without it, including, in several brackets, QuickPlate.</p>

<h2>The Pattern</h2>

<p>RecipeBox's competition record is a clean illustration of how this format works. It won first-round matchups almost automatically: no auth, clean page, obvious task. It won second-round matchups by demonstrating interactivity that more static apps couldn't match. It lost finals to Spendly because Spendly's persistent multi-step interaction — transactions accumulating, budget updating, warnings triggering — is a richer demonstration than a servings adjustment, even a very clean one.</p>

<p>That's not a criticism. That's a well-designed competition surfacing a meaningful quality difference.</p>

<h2>For Human Users</h2>

<p>This is a genuinely good recipe page. The metadata is complete, the instructions are clear, the servings calculator is useful, and the related-recipe links give you somewhere to go next. For a single dish, it does everything you need.</p>

<p>The limitations are scope, not quality. No search, no browse, no personalization, no shopping list, no timers. Whether the rest of the site delivers on the promise of this page is worth finding out. Given who built it, we'd bet on yes.</p>
HTML,
            ],

            // ── Article 6: Pathlight ─────────────────────────────────────────
            [
                'section'     => $victoryGames,
                'title'       => 'Pathlight — An Entrant Profile',
                'slug'        => 'pathlight-vibecode-victory-games-entrant-profile',
                'excerpt'     => 'Sabuhi Guliyev finished building Pathlight faster than anyone else finished their app. A professional software developer, he made a sprint planner that never failed once. It still went 2-5, and the reason is instructive.',
                'is_featured' => false,
                'content'     => <<<HTML
<ul>
  <li>Built by Sabuhi Guliyev — professional software developer, finished his app the fastest</li>
  <li>URL: pathlight-blond.vercel.app — generates a 4-step sprint plan as an actionable checklist, instantly, no auth</li>
  <li>2 wins, 5 losses — never failed; lost on perceived scope, not execution</li>
  <li>Strong baseline: immediate correct output, clear status messages, zero errors across all runs</li>
  <li>Lost late to apps with deeper interaction richness, specifically Spendly's persistent budgeting loop</li>
  <li>Human gaps: weak editing/reordering affordances, no export or sharing, limited keyboard/ARIA support</li>
</ul>

<p>Sabuhi Guliyev finished building Pathlight faster than anyone else in the competition finished their app. Sabuhi is a professional software developer, and it showed — not in the complexity of what he built, but in the completeness of it. Pathlight was clean, it was deployed, and it worked exactly as intended from the moment he submitted it.</p>

<p>It went 2-5. None of the losses were Pathlight's fault.</p>

<h2>The App</h2>

<p>Pathlight is a sprint planning tool. Give it a goal or project description and it generates a simple four-step plan, rendered as an actionable checklist, immediately. No account, no configuration, no waiting. Input goes in, checklist comes out.</p>

<h2>Why It Worked</h2>

<p>Pathlight did the things that matter in this format: no authentication barrier, a single obvious primary task, immediate correct output, clear confirmation that the task completed. An agent landing on Pathlight could accomplish the goal in the minimum number of steps and verify the result in the screenshots. In a field where two apps never cleared Round 1 because of auth walls, and where several others had navigation or error issues, Pathlight's clean execution was genuinely competitive. It beat opponents with real problems.</p>

<h2>Why It Lost</h2>

<p>Pathlight's losses came against apps that did the same things — no friction, correct output, clear confirmation — and also had more to demonstrate. Spendly's multi-step interaction loop produces richer evidence of agent success than a single-step plan generation. A judge evaluating "accomplished something significant" can look at a Spendly run and see six transactions, a running total, a budget warning, and a materially different page state. A Pathlight run shows a prompt and a checklist.</p>

<p>The checklist is correct. It's immediately useful. It just doesn't accumulate.</p>

<p>This is a limitation of the competition format as much as the app. A sprint planning tool's value plays out over the week after you make the plan, not in the three seconds it takes to generate it. AI agent testing captures the moment of generation, not the week of execution.</p>

<h2>For Human Users</h2>

<p>Pathlight is a genuinely useful tool for low-ceremony sprint planning. Turn a vague objective into an actionable outline, quickly, without heavyweight project management setup. The output is clean and the interaction is instant.</p>

<p>The gaps are real for sustained use: checklist items are difficult to reorder or edit, there's no way to export or share the plan, and keyboard and ARIA support could be stronger. A good first step, ready for a second.</p>
HTML,
            ],

            // ── Article 7: CraftCode ─────────────────────────────────────────
            [
                'section'     => $victoryGames,
                'title'       => 'CraftCode — An Entrant Profile',
                'slug'        => 'craftcode-vibecode-victory-games-entrant-profile',
                'excerpt'     => 'Sampath Pranay Beela\'s CraftCode worked. The flow was clean, the agent completed it correctly every time, and the report it produced was well-structured. It went 2-5 because the repos it analyzed had problems. That\'s not the same thing.',
                'is_featured' => false,
                'content'     => <<<HTML
<ul>
  <li>Built by Sampath Pranay Beela</li>
  <li>URL: codecraft-3gnu.onrender.com — paste a GitHub URL, get a redirected report with letter grade, issue counts, severity metrics</li>
  <li>2 wins, 5 losses — won when rivals broke on auth; lost because judges penalized the analyzed repo's bad results, not CraftCode's UX</li>
  <li>The competition's most interesting edge case: the tool worked; the output looked like failure</li>
  <li>The actual human experience is solid: one-step audit, clean entry point, clear severity-forward report</li>
  <li>Lesson for future judging rubrics: distinguish tool performance from tool output</li>
</ul>

<p>CraftCode finished the first VibeCode Victory Games with a 2-5 record and a legitimate grievance. Sampath Pranay Beela built a tool that did its job correctly in every single run. The AI judge didn't notice.</p>

<p>The app does one thing: paste a GitHub repository URL, click analyze, and get redirected to a report page showing a letter grade, an issue count broken down by severity, and key metrics about the codebase. The flow is clean, the redirect is automatic, and the report is well-structured. CraftCode performed exactly as designed in every bracket.</p>

<p>CraftCode lost because the repositories it analyzed had problems.</p>

<h2>What Happened in the Brackets</h2>

<p>An AI judge evaluating a competition run sees the agent's actions, the resulting page state, and the screenshots. When CraftCode analyzed a repository and returned a report showing a D grade and seventeen critical issues, the judge saw a page dominated by negative signals. In multiple brackets, that page was evaluated as a poor user experience — which is a reasonable interpretation of a page covered in red warnings, and also a category error.</p>

<p>CraftCode wasn't showing a poor user experience. It was showing an honest assessment of a poor codebase. The tool did its job correctly. The judge evaluated the output, not the tool.</p>

<p>An app that tells you your code has problems is not the same as an app that has problems. A doctor who delivers a bad diagnosis isn't a bad doctor. We're updating the rubric for next time.</p>

<h2>Where It Won</h2>

<p>CraftCode's two wins came in first-round matchups against apps with authentication barriers or navigation failures. When an opponent couldn't get an agent through to any primary functionality, CraftCode's complete, redirected, structured report was the obvious winner. The app's core flow is reliable enough to beat real competition — when that competition is functional.</p>

<h2>For Human Users</h2>

<p>Paste a URL, get a report. The grade and severity breakdown give you immediate orientation to a codebase's health — useful for quick triage, evaluating a dependency, or understanding what you're inheriting. The flow is as low-friction as it gets.</p>

<p>The result in this competition was unfair. CraftCode deserved better, and we'll make sure the rubric reflects that next time.</p>
HTML,
            ],

            // ── Article 8: Brian Munroe ──────────────────────────────────────
            [
                'section'     => $victoryGames,
                'title'       => 'Brand Compliance Checker — An Entrant Profile',
                'slug'        => 'brand-compliance-checker-vibecode-victory-games-entrant-profile',
                'excerpt'     => 'Brian Munroe built a genuinely interesting brand compliance tool during the competition. He submitted the wrong URL. The right URL shows a working app.',
                'is_featured' => false,
                'content'     => <<<HTML
<ul>
  <li>Built by Brian Munroe</li>
  <li>The app: Brand Compliance Checker — upload a creative asset, compare it against saved brand rules (colors, typography, logos), get a compliance report</li>
  <li>Correct URL: mad-virid.vercel.app — working app with saved rules, run history, and a full compliance workflow</li>
  <li>Submitted URL: a different Vercel deployment that wasn't properly set up — OAuth-gated, blocked agents in Round 1 across all 5 brackets</li>
  <li>A successful run during the competition period confirms the real app worked; it's visible on Brian's victor profile</li>
  <li>Lesson: double-check the URL before the deadline; Vercel preview deployments proliferate fast</li>
</ul>

<p>Brian Munroe built something genuinely interesting for the first VibeCode Victory Games. The Brand Compliance Checker lets you define a set of brand rules — colors, typography entries, logo guidelines, things not to do — and then upload a creative asset to check against those rules. The app analyzes the creative, flags compliance issues, and stores a history of checks with issue counts and resolution status.</p>

<p>This is a real tool for a real workflow. Design teams deal with brand compliance constantly, and a quick automated check against a saved ruleset is exactly the kind of utility that saves time in practice.</p>

<p>Brian submitted the wrong URL.</p>

<h2>What Was Submitted</h2>

<p>The URL Brian submitted pointed to a Vercel deployment that, at the time the competition brackets ran, presented an OAuth login gate with no alternative path. Every agent, in every bracket, arrived at the login screen and couldn't proceed. Five first-round losses, all for the same reason.</p>

<p>This isn't unusual. Authentication barriers are the most common reason entries lose early in this competition format. But in Brian's case it was compounded: it wasn't just an auth wall, it was the wrong deployment entirely.</p>

<h2>What Brian Actually Built</h2>

<p>The correct URL is <a href="https://mad-virid.vercel.app/">mad-virid.vercel.app</a>. It shows the Brand Compliance Checker in working order: a home screen with navigation to Settings and Primitives, a saved ruleset (15 colors, 8 typography entries, 3 logo rules, 5 don'ts), a file upload interface for running checks, and a Recent Checks history showing past results with issue counts.</p>

<p>A separate AIUXTester run during the competition period — visible on Brian's victor profile — confirms the app was functional during the competition window. He built it. He deployed it. He submitted the wrong link.</p>

<p>Deployment URLs on platforms like Vercel proliferate fast: preview deployments, branch deployments, old builds all get their own URLs and they all look similar under time pressure. It's an easy mistake to make when you're building fast against a deadline.</p>

<h2>What This Means</h2>

<p>For Brian: his competition result doesn't reflect what he built. His working run is public and his profile tells the real story.</p>

<p>For future competitors: the URL you submit is the URL that gets tested. It's worth a final check before the deadline — open it in an incognito window, make sure an unauthenticated visitor can reach your primary functionality, and confirm it's the deployment you meant to submit.</p>

<p>Brian, the app looks good. Run it back in May.</p>
HTML,
            ],

            // ── Article 9: Lumnia ────────────────────────────────────────────
            [
                'section'     => $victoryGames,
                'title'       => 'Lumnia — An Entrant Profile',
                'slug'        => 'lumnia-vibecode-victory-games-entrant-profile',
                'excerpt'     => 'Lumnia is an AI project assistant that generates structured plans and reports. It lost all five brackets in Round 1. The one time an agent got through, the product worked. That gap is entirely fixable.',
                'is_featured' => false,
                'content'     => <<<HTML
<ul>
  <li>URL: lumnia.vercel.app — AI project assistant, turns prompts or repo context into structured plans or research-style reports</li>
  <li>Lost all 5 brackets in Round 1: mandatory auth, no guest path, no obvious CTA before login</li>
  <li>The one bracket where an agent got through: generated a clean 4-step checklist — the underlying product clearly works</li>
  <li>Entire value proposition hidden behind a login wall with no preview or sample output</li>
  <li>Fix is straightforward: demo mode, sample project, or a public example output would let the product speak before the signup form does</li>
</ul>

<p>Lumnia is one of the more frustrating entries to write about, because the product is interesting and the competition result tells you almost nothing about it.</p>

<p>The app is an AI project assistant. Give it a prompt or point it at a repository, and it generates structured, actionable output — step-by-step plans, research-style reports, organized checklists. For the kind of vague-objective-to-concrete-plan workflow that developers and project leads deal with constantly, this is a genuinely useful category of tool.</p>

<p>None of that was visible to the agents.</p>

<h2>What Happened in the Brackets</h2>

<p>Lumnia requires an account. Every agent found a login screen. No guest path, no public sample output, no demo mode, no landing page feature that worked without authentication. In four of five brackets, the session ended at the login screen.</p>

<p>In the fifth, an agent got through and reached the core product. It generated a clean four-step checklist for a project prompt — well-structured, clearly presented, actionable. That bracket still ended in a loss, but the one glimpse of the actual product suggests there's something real there.</p>

<h2>The Gap</h2>

<p>The gap between what Lumnia does and what the competition saw it do is entirely explained by its access model, and it's fixable. A demo mode that pre-loads a sample project and lets visitors see the output without creating an account would change the first-round trajectory immediately. Even a static public example — "here's the kind of plan Lumnia generates" — gives a judge something to evaluate beyond a login form.</p>

<p>AI judging rewards apps that make their primary value immediately visible. Lumnia's primary value is completely invisible to anyone who hasn't created an account. For human users who've heard about the tool and are willing to sign up, that might be acceptable. For AI agents in a competitive bracket, it's a first-round exit every time.</p>

<h2>For Human Users</h2>

<p>If the product delivers what the one successful run suggested — and the evidence says it might — Lumnia is worth a look. The recommendation: add a guest mode. Let the product speak before the signup form does.</p>
HTML,
            ],

            // ── Article 10: Road Hazard ──────────────────────────────────────
            [
                'section'     => $victoryGames,
                'title'       => 'Road Hazard — An Entrant Profile',
                'slug'        => 'road-hazard-vibecode-victory-games-entrant-profile',
                'excerpt'     => 'Akshat Pandey, Landen Ingerslev, and Theodore Tibbs are undergrad freshmen. They sat in the front row, built a crash reporting app from scratch in two hours, and ran out of time to optimize it for AI judging. With more runway, they could have been a real contender.',
                'is_featured' => false,
                'content'     => <<<HTML
<ul>
  <li>Built by Akshat Pandey, Landen Ingerslev, and Theodore Tibbs — undergrad freshmen who sat in the front row</li>
  <li>URL: profound-kulfi-dc850d.netlify.app — Road Hazard, a crash reporting and road safety app</li>
  <li>The app worked: users can report road accidents and see where incidents are happening near them</li>
  <li>Lost all 5 brackets in Round 1: ran out of time to craft agent-friendly task flows and optimize the submission</li>
  <li>With more time — better-crafted agent intents, multiple test runs, a tuned submission — they could have been a contender</li>
</ul>

<p>Akshat Pandey, Landen Ingerslev, and Theodore Tibbs are undergrad freshmen. They came to the competition, sat in the front row, and built a crash reporting app from scratch in two hours. That alone is worth noting.</p>

<p>Road Hazard lets users report road accidents and see where incidents are happening near them — a genuinely useful civic safety tool that addresses something real. An app that helps drivers and communities understand where roads are dangerous is a meaningful thing to build, and they built it under time pressure as first-year undergraduates at their first vibecoding competition.</p>

<h2>What Happened in the Brackets</h2>

<p>Road Hazard lost all five brackets in Round 1. The issue wasn't that the app didn't work — it did. The issue was time. Crafting the kind of agent-friendly task flow that AI judging rewards takes iteration: you run a test, see where the agent gets confused, adjust the interface or the submission details, run again. With two hours on the clock to build the app itself, there wasn't time left to optimize the judging experience.</p>

<p>The agents that approached Road Hazard didn't have a clear, frictionless path to the app's primary value — reporting an incident and seeing results near you — in the way that the winning entries did. That's a tuning problem, not a product problem.</p>

<h2>What Could Have Been</h2>

<p>AI judging in this format rewards a specific set of conditions: no auth barrier, an obvious primary task, and immediate verifiable evidence that the task completed. Road Hazard has all the ingredients for that: a simple reporting flow, location-based results, visible data. With more time to run test sessions, craft clearer agent task descriptions, and iterate on the submission, this team could have cleared Round 1 and gone deep.</p>

<p>They're freshmen. They have a lot of competitions ahead of them.</p>

<h2>Come Back in May</h2>

<p>The next VibeCode Victory Games is May 26th at the NERD Center. Road Hazard is a good app. Akshat, Landen, and Theodore are exactly the kind of builders this competition is for. We hope they come back with more time to run their own tests before submission day, because the app they built deserves a better bracket result than this one produced.</p>
HTML,
            ],
        ];

        foreach ($articles as $data) {
            $section = $data['section'];
            unset($data['section']);

            $article = Article::firstOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'section_id'   => $section->id,
                    'status'       => 'published',
                    'access'       => 'public',
                    'published_at' => $publishedAt,
                ])
            );

            if (!$article->contributors()->where('contributors.id', $staff->id)->exists()) {
                $article->contributors()->attach($staff->id, ['role' => 'author']);
            }
        }
    }
}
