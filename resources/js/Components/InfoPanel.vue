<script setup>
// No script logic needed for a static component
</script>

<template>
    <div class="p-6 bg-gray-800 shadow-sm sm:rounded-lg">
        <h3 class="text-lg font-semibold text-indigo-400 mb-4">How It Works</h3>
        <div class="space-y-4 text-sm text-gray-300">
            <div>
                <h4 class="font-bold text-white">Notifications & To-Dos</h4>
                <p>Stay engaged! The <span class="font-bold">To-Do list</span> (checklist icon) guides you on tasks like setting goals and liking posts. The <span class="font-bold">Notifications</span> (bell icon) keep you updated on new posts, comments, and likes from others.</p>
            </div>
            <div>
                <h4 class="font-bold text-white">Leaderboard & Points</h4>
                <p>Your activity earns you points and a spot on the leaderboard. Points are awarded for posting, commenting, and receiving likes on your content. The more you engage, the more points you earn!</p>
                <ul class="list-disc list-inside mt-2 pl-2 text-gray-400">
                    <li><span class="font-semibold">Daily Post:</span> 3 points (awarded once per day)</li>
                    <li><span class="font-semibold">Comment:</span> 1 point</li>
                    <li><span class="font-semibold">Like Given:</span> 0.5 points</li>
                    <li><span class="font-semibold">Like Received:</span> 1 point</li>
                    <li><span class="font-semibold">Read an Update:</span> 0.5 point</li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-white">Application Invitation</h4>
                <p>Your goal is to reach 100 points. Once at least five members have reached 100 points, the first round of application invitations will be sent out. After that, any member who reaches 100 points will automatically receive an invitation to apply for the first ritual cycle.</p>
            </div>
        </div>
    </div>
</template>
