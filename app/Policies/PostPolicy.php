<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Post $post): bool
    {
        return $user->is($post->user);
    }

    public function delete(User $user, Post $post): bool
    {
        if ($user->is($post->user)) {
            return true;
        }

        // Check if the user is an admin of the post's group.
        if ($post->group) {
            $membership = $user->groups()->where('group_id', $post->group_id)->first();
            return $membership && ($membership->pivot->role === 'admin' || $post->group->creator_id === $user->id);
        }

        return false;
    }
}
