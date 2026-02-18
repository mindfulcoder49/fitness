<script setup>
import { ref, computed } from 'vue';
import { router, Link, usePage, useForm } from '@inertiajs/vue3';
import GroupAvailabilityPanel from '@/Components/Admin/GroupAvailabilityPanel.vue';
import GroupMeetupPanel from '@/Components/Admin/GroupMeetupPanel.vue';
import GroupLikeStatsPanel from '@/Components/Admin/GroupLikeStatsPanel.vue';

const props = defineProps({
    group: Object,
    availabilitySummary: Array,
});

const user = usePage().props.auth.user;
const appUrl = usePage().props.appUrl;
const activeTab = ref('availability');
const invitationLinkCopied = ref(false);

const invitationLink = computed(() => `${appUrl}/register?group=${props.group.id}`);

const copyInvitationLink = () => {
    navigator.clipboard.writeText(invitationLink.value).then(() => {
        invitationLinkCopied.value = true;
        setTimeout(() => { invitationLinkCopied.value = false; }, 2000);
    });
};
const groupRoles = ['member', 'admin'];
const editingTask = ref(null);
const sendingEmail = ref(false);
const emailStatus = ref(null);

const deleteGroup = () => {
    if (confirm('Are you sure you want to permanently delete this group? All posts, comments, likes, tasks, meetups, and messages will be lost. This cannot be undone.')) {
        router.delete(route('admin.groups.destroy', { group: props.group.id }));
    }
};

const launchGroup = () => {
    if (confirm('Are you sure you want to launch this group? This will not send the launch email — use "Send Launch Email" separately if needed.')) {
        router.patch(route('admin.groups.launch', { group: props.group.id }), {}, {
            preserveScroll: true,
        });
    }
};

const unlaunchGroup = () => {
    if (confirm('Are you sure you want to unlaunch this group? Members will see the "Gathering Members" state until the group is relaunched.')) {
        router.patch(route('admin.groups.unlaunch', { group: props.group.id }), {}, {
            preserveScroll: true,
        });
    }
};

const minMembersValue = ref(props.group.min_members);

const updateMinMembers = () => {
    router.patch(route('admin.groups.update-min-members', { group: props.group.id }), {
        min_members: minMembersValue.value || null,
    }, { preserveScroll: true });
};

const sendLaunchEmail = () => {
    if (confirm('Are you sure you want to send the launch email to all members?')) {
        sendingEmail.value = true;
        emailStatus.value = null;
        router.post(route('admin.groups.send-launch-email', { group: props.group.id }), {}, {
            preserveScroll: true,
            onSuccess: (page) => {
                const flash = page.props?.flash;
                if (flash?.error) {
                    emailStatus.value = { type: 'error', message: flash.error };
                } else if (flash?.success) {
                    emailStatus.value = { type: 'success', message: flash.success };
                } else {
                    emailStatus.value = { type: 'success', message: 'Request completed.' };
                }
            },
            onError: (errors) => {
                emailStatus.value = { type: 'error', message: 'Request failed: ' + JSON.stringify(errors) };
            },
            onFinish: () => {
                sendingEmail.value = false;
            },
        });
    }
};

const taskForm = useForm({
    title: '',
    description: '',
    is_current: false,
});

const allComments = computed(() => {
    return props.group.posts.flatMap(post => post.comments || []);
});

const allLikes = computed(() => {
    const postLikes = props.group.posts.flatMap(post => (post.likes || []).map(like => ({ ...like, on: 'Post' })));
    const commentLikes = allComments.value.flatMap(comment => (comment.likes || []).map(like => ({ ...like, on: 'Comment' })));
    return [...postLikes, ...commentLikes];
});

const updateGroupMemberRole = (user, newRole) => {
    router.patch(route('admin.groups.members.update-role', { group: props.group.id, user: user.id }), {
        role: newRole,
    }, { preserveScroll: true });
};

const deleteItem = (type, id) => {
    if (confirm(`Are you sure you want to delete this ${type}? This action cannot be undone.`)) {
        let routeName;
        let param;
        if (type === 'post') routeName = 'admin.posts.destroy', param = { post: id };
        else if (type === 'like') routeName = 'admin.likes.destroy', param = { like: id };
        else if (type === 'comment') routeName = 'admin.comments.destroy', param = { comment: id };
        
        if(routeName) {
            router.delete(route(routeName, param), { preserveScroll: true });
        }
    }
};

const updateBlogPostStatus = (post) => {
    router.patch(route('admin.posts.toggle-blog', { post: post.id }), {
        is_blog_post: post.is_blog_post,
    }, { preserveScroll: true });
};

const updateGroupPublicStatus = (group, event) => {
    router.patch(route('admin.groups.toggle-public', { group: group.id }), {
        is_public: event.target.checked,
    }, { preserveScroll: true });
};

const submitTask = () => {
    if (editingTask.value) {
        taskForm.patch(route('admin.groups.tasks.update', { task: editingTask.value.id }), {
            onSuccess: () => {
                editingTask.value = null;
                taskForm.reset();
            },
        });
    } else {
        taskForm.post(route('admin.groups.tasks.store', { group: props.group.id }), {
            onSuccess: () => taskForm.reset(),
        });
    }
};

const editTask = (task) => {
    editingTask.value = task;
    taskForm.title = task.title;
    taskForm.description = task.description;
    taskForm.is_current = task.is_current;
};

const cancelEdit = () => {
    editingTask.value = null;
    taskForm.reset();
};

const deleteTask = (task) => {
    if (confirm('Are you sure you want to delete this task?')) {
        router.delete(route('admin.groups.tasks.destroy', { task: task.id }), { preserveScroll: true });
    }
};

const setCurrentTask = (task) => {
    router.patch(route('admin.groups.tasks.set-current', { task: task.id }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            task.is_current = true; // Update the task's status locally
        },
    });
};

const unsetCurrentTask = (task) => {
    router.patch(route('admin.groups.tasks.unset-current', { task: task.id }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            task.is_current = false; // Update the task's status locally
        },
    });
};
</script>

<template>
    <div class="p-2 sm:p-6 text-theme-text-primary">
        <div class="mb-4 border-b border-theme-border">
            <div class="overflow-x-auto">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button @click="activeTab = 'availability'" :class="['whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm', activeTab === 'availability' ? 'border-theme-accent text-theme-accent-text' : 'border-transparent text-theme-text-muted hover:text-theme-text-primary hover:border-theme-border']">
                        Availability
                    </button>
                    <button @click="activeTab = 'meetups'" :class="[activeTab === 'meetups' ? 'border-theme-accent text-theme-accent-text' : 'border-transparent text-theme-text-muted hover:border-theme-border hover:text-theme-text-primary', 'whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium']">Meetups</button>
                    <button @click="activeTab = 'members'" :class="[activeTab === 'members' ? 'border-theme-accent text-theme-accent-text' : 'border-transparent text-theme-text-muted hover:border-theme-border hover:text-theme-text-primary', 'whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium']">Members</button>
                    <button @click="activeTab = 'posts'" :class="[activeTab === 'posts' ? 'border-theme-accent text-theme-accent-text' : 'border-transparent text-theme-text-muted hover:border-theme-border hover:text-theme-text-primary', 'whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium']">Posts</button>
                    <button @click="activeTab = 'comments'" :class="[activeTab === 'comments' ? 'border-theme-accent text-theme-accent-text' : 'border-transparent text-theme-text-muted hover:border-theme-border hover:text-theme-text-primary', 'whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium']">Comments</button>
                    <button @click="activeTab = 'likes'" :class="[activeTab === 'likes' ? 'border-theme-accent text-theme-accent-text' : 'border-transparent text-theme-text-muted hover:border-theme-border hover:text-theme-text-primary', 'whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium']">Likes</button>
                    <button @click="activeTab = 'like-stats'" :class="[activeTab === 'like-stats' ? 'border-theme-accent text-theme-accent-text' : 'border-transparent text-theme-text-muted hover:border-theme-border hover:text-theme-text-primary', 'whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium']">Like Stats</button>
                    <button @click="activeTab = 'tasks'" :class="[activeTab === 'tasks' ? 'border-theme-accent text-theme-accent-text' : 'border-transparent text-theme-text-muted hover:border-theme-border hover:text-theme-text-primary', 'whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium']">Tasks</button>
                    <button v-if="user.is_admin" @click="activeTab = 'settings'" :class="[activeTab === 'settings' ? 'border-theme-accent text-theme-accent-text' : 'border-transparent text-theme-text-muted hover:border-theme-border hover:text-theme-text-primary', 'whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium']">Settings</button>
                </nav>
            </div>
        </div>

        <div>
            <div v-if="activeTab === 'availability'">
                <GroupAvailabilityPanel :availability-summary="availabilitySummary" />
            </div>
            <div v-if="activeTab === 'meetups'">
                <GroupMeetupPanel :group="group" />
            </div>
            <!-- Members Table -->
            <div v-if="activeTab === 'members'" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-theme-border">
                    <thead class="bg-theme-elevated/50">
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-theme-text-primary sm:pl-6">Member</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-theme-text-primary">Role</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-theme-text-primary">Points</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-theme-border">
                        <tr v-for="member in group.users" :key="member.id">
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-theme-text-primary sm:pl-6">
                                <Link :href="route('users.show', { user: member.username })" class="hover:underline">{{ member.name }}</Link>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm">
                                <select :value="member.pivot.role" @change="updateGroupMemberRole(member, $event.target.value)" :disabled="member.id === group.creator_id" class="block w-full rounded-md border-theme-border bg-theme-card py-1.5 pl-3 pr-10 text-theme-text-primary focus:border-theme-accent focus:outline-none focus:ring-theme-accent-ring sm:text-sm disabled:opacity-50">
                                    <option v-for="role in groupRoles" :key="role" :value="role">{{ role }}</option>
                                </select>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-theme-text-secondary">{{ member.pivot.points }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Posts Table -->
            <div v-if="activeTab === 'posts'" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-theme-border">
                    <thead class="bg-theme-elevated/50">
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-theme-text-primary sm:pl-6">Author</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-theme-text-primary">Content</th>
                            <th class="px-3 py-3.5 text-center text-sm font-semibold text-theme-text-primary">Is Blog</th>
                            <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-theme-border">
                        <tr v-for="post in group.posts" :key="post.id">
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-theme-text-primary sm:pl-6">{{ post.user.name }}</td>
                            <td class="px-3 py-4 text-sm text-theme-text-secondary max-w-md truncate">
                                {{ post.content }}
                                <p v-if="post.group_task" class="text-xs text-theme-accent-text">Task: {{ post.group_task.title }}</p>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-center text-sm">
                                <input type="checkbox" v-model="post.is_blog_post" @change="updateBlogPostStatus(post)" class="h-4 w-4 rounded border-theme-border bg-theme-card text-theme-accent focus:ring-theme-accent-ring" />
                            </td>
                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                <button @click="deleteItem('post', post.id)" class="text-theme-danger hover:text-theme-danger-hover">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Comments Table -->
            <div v-if="activeTab === 'comments'" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-theme-border">
                    <thead class="bg-theme-elevated/50">
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-theme-text-primary sm:pl-6">Author</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-theme-text-primary">Content</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-theme-text-primary">Date</th>
                            <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-theme-border">
                        <tr v-for="comment in allComments" :key="comment.id">
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-theme-text-primary sm:pl-6">{{ comment.user.name }}</td>
                            <td class="px-3 py-4 text-sm text-theme-text-secondary max-w-md truncate">{{ comment.content }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-theme-text-muted">{{ new Date(comment.created_at).toLocaleDateString() }}</td>
                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                <button @click="deleteItem('comment', comment.id)" class="text-theme-danger hover:text-theme-danger-hover">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Likes Table -->
            <div v-if="activeTab === 'likes'" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-theme-border">
                    <thead class="bg-theme-elevated/50">
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-theme-text-primary sm:pl-6">User</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-theme-text-primary">Liked Item Type</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-theme-text-primary">Date</th>
                            <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-theme-border">
                        <tr v-for="like in allLikes" :key="like.id">
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-theme-text-primary sm:pl-6">{{ like.user.name }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-theme-text-secondary">{{ like.on }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-theme-text-muted">{{ new Date(like.created_at).toLocaleDateString() }}</td>
                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                <button @click="deleteItem('like', like.id)" class="text-theme-danger hover:text-theme-danger-hover">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Like Stats Tab -->
            <div v-if="activeTab === 'like-stats'">
                <GroupLikeStatsPanel :group="group" />
            </div>

            <!-- Tasks Tab -->
            <div v-if="activeTab === 'tasks'">
                <h4 class="text-lg font-semibold mb-4 text-theme-text-primary">Manage Group Tasks</h4>
                <div class="bg-theme-page/50 p-4 rounded-lg mb-6">
                    <form @submit.prevent="submitTask">
                        <h5 class="font-semibold mb-2 text-theme-text-primary">{{ editingTask ? 'Edit Task' : 'Create New Task' }}</h5>
                        <div class="space-y-3">
                            <input v-model="taskForm.title" placeholder="Task Title" class="w-full bg-theme-card border-theme-border text-theme-text-primary rounded-md" required />
                            <textarea v-model="taskForm.description" placeholder="Task Description" class="w-full bg-theme-card border-theme-border text-theme-text-primary rounded-md"></textarea>
                            <div v-if="editingTask" class="flex items-center">
                                <input type="checkbox" v-model="taskForm.is_current" id="is_current_task" class="h-4 w-4 rounded border-theme-border bg-theme-card text-theme-accent focus:ring-theme-accent-ring" />
                                <label for="is_current_task" class="ml-2 text-sm font-medium text-theme-text-primary">Current Task</label>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center space-x-2">
                            <button type="submit" class="px-3 py-1.5 text-sm bg-theme-accent hover:bg-theme-accent-hover text-white rounded-md">{{ editingTask ? 'Update Task' : 'Create Task' }}</button>
                            <button v-if="editingTask" @click="cancelEdit" type="button" class="px-3 py-1.5 text-sm bg-theme-elevated hover:bg-theme-elevated text-theme-text-primary rounded-md">Cancel</button>
                        </div>
                    </form>
                </div>

                <h5 class="font-semibold mb-2 text-theme-text-primary">Existing Tasks</h5>
                <ul class="space-y-3">
                    <li v-for="task in group.tasks" :key="task.id" class="p-3 bg-theme-page/50 rounded-lg flex justify-between items-center">
                        <div>
                            <p class="font-bold text-theme-text-primary" :class="{'!text-theme-accent-text': task.is_current}">{{ task.title }} <span v-if="task.is_current" class="text-xs font-normal">(Current)</span></p>
                            <p class="text-sm text-theme-text-muted">{{ task.description }}</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button @click="setCurrentTask(task)" v-if="!task.is_current" class="text-xs text-theme-success hover:text-theme-success/80">Set Current</button>
                            <button @click="unsetCurrentTask(task)" v-if="task.is_current" class="text-xs text-theme-warning hover:text-theme-warning/80">Unset Current</button>
                            <button @click="editTask(task)" class="text-xs text-theme-link hover:text-theme-link-hover">Edit</button>
                            <button @click="deleteTask(task)" class="text-xs text-theme-danger hover:text-theme-danger-hover">Delete</button>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Settings Tab -->
            <div v-if="activeTab === 'settings' && user.is_admin">
                <h4 class="text-lg font-semibold mb-4 text-theme-text-primary">Group Settings</h4>
                <div class="flex items-center">
                    <input type="checkbox" :checked="!!group.is_public" @change="updateGroupPublicStatus(group, $event)" :id="`public-toggle-${group.id}`" class="h-4 w-4 rounded border-theme-border bg-theme-card text-theme-accent focus:ring-theme-accent-ring" />
                    <label :for="`public-toggle-${group.id}`" class="ml-2 text-sm font-medium text-theme-text-primary">Public Group</label>
                </div>
                <p class="text-xs text-theme-text-faint mt-1">If checked, this group will be listed on the public groups page and anyone can join.</p>

                <div class="mt-6 pt-6 border-t border-theme-border">
                    <h5 class="text-sm font-semibold mb-2 text-theme-text-primary">Minimum Members to Launch</h5>
                    <p class="text-xs text-theme-text-muted mb-3">Set the minimum number of members required before the group launches. Leave empty for no minimum (group launches immediately).</p>
                    <div class="flex items-center space-x-3">
                        <input
                            type="number"
                            v-model.number="minMembersValue"
                            min="1"
                            placeholder="No minimum"
                            class="w-32 rounded-md border-theme-border bg-theme-input text-theme-text-primary text-sm px-3 py-1.5 focus:border-theme-accent focus:ring-theme-accent-ring"
                        />
                        <button
                            @click="updateMinMembers"
                            class="px-3 py-1.5 text-sm bg-theme-accent hover:bg-theme-accent-hover text-white rounded-md"
                        >
                            Save
                        </button>
                    </div>
                    <p class="text-xs text-theme-text-faint mt-1">Current: {{ group.min_members ?? 'None (launches immediately)' }} &middot; Members: {{ group.users.length }} &middot; Status: {{ group.launched_at ? 'Launched' : 'Not launched' }}</p>
                    <div v-if="!group.launched_at" class="mt-3">
                        <button
                            @click="launchGroup"
                            class="px-3 py-1.5 text-sm bg-theme-success hover:bg-theme-success/80 text-white rounded-md"
                        >
                            Launch Group
                        </button>
                        <p class="text-xs text-theme-text-faint mt-1">Manually launch this group regardless of member count.</p>
                    </div>
                    <div v-if="group.launched_at && group.min_members" class="mt-3">
                        <button
                            @click="unlaunchGroup"
                            class="px-3 py-1.5 text-sm bg-theme-danger hover:bg-theme-danger-hover text-white rounded-md"
                        >
                            Unlaunch Group
                        </button>
                        <p class="text-xs text-theme-text-faint mt-1">This will reset the group to "Gathering Members" state.</p>
                    </div>
                    <p v-if="group.launched_at && !group.min_members" class="text-xs text-theme-text-faint mt-2">Set a minimum member count above to enable unlaunching.</p>
                </div>

                <div class="mt-6 pt-6 border-t border-theme-border">
                    <h5 class="text-sm font-semibold mb-2 text-theme-text-primary">Launch Email</h5>
                    <p class="text-xs text-theme-text-muted mb-3">Send the group launch email to all members. This will also set the group as launched if it hasn't been already.</p>
                    <button
                        @click="sendLaunchEmail"
                        class="px-4 py-2 text-sm bg-theme-accent hover:bg-theme-accent-hover text-white rounded-md"
                        :disabled="sendingEmail"
                    >
                        {{ sendingEmail ? 'Sending...' : 'Send Launch Email to All Members' }}
                    </button>
                    <div v-if="emailStatus" class="mt-3 p-3 rounded-md text-sm" :class="emailStatus.type === 'success' ? 'bg-theme-success/20 text-theme-success border border-theme-success' : 'bg-theme-danger/20 text-theme-danger border border-theme-danger'">
                        {{ emailStatus.message }}
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-theme-border">
                    <h5 class="text-sm font-semibold mb-2 text-theme-text-primary">Invitation Link</h5>
                    <p class="text-xs text-theme-text-muted mb-3">Share this link to invite people to register and automatically join this group.</p>
                    <div class="flex items-center space-x-3">
                        <input
                            type="text"
                            :value="invitationLink"
                            readonly
                            class="flex-1 rounded-md border-theme-border bg-theme-input text-theme-text-primary text-sm px-3 py-1.5 focus:border-theme-accent focus:ring-theme-accent-ring"
                        />
                        <button
                            @click="copyInvitationLink"
                            class="px-3 py-1.5 text-sm bg-theme-accent hover:bg-theme-accent-hover text-white rounded-md whitespace-nowrap"
                        >
                            {{ invitationLinkCopied ? 'Copied!' : 'Copy Link' }}
                        </button>
                    </div>
                    <p class="text-xs text-theme-text-faint mt-1">Anyone who registers via this link will be added to the group as a member.</p>
                </div>

                <div class="mt-6 pt-6 border-t border-theme-danger/30">
                    <h5 class="text-sm font-semibold text-theme-danger mb-2">Danger Zone</h5>
                    <p class="text-xs text-theme-text-muted mb-3">Permanently delete this group, all its posts, comments, likes, tasks, meetups, and messages. This action cannot be undone.</p>
                    <button
                        @click="deleteGroup"
                        class="px-4 py-2 text-sm bg-theme-danger hover:bg-theme-danger-hover text-white rounded-md"
                    >
                        Delete Group
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
