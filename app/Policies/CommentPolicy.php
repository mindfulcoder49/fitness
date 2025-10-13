<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Comment  $comment
     * @return bool
     */
    public function delete(User $user, Comment $comment): bool
    {
        if ($user->is($comment->user)) {
            return true;
        }

        // Check if the user is an admin of the comment's group.
        if ($comment->post && $comment->post->group) {
            $group = $comment->post->group;
            $membership = $user->groups()->where('group_id', $group->id)->first();
            return $membership && ($membership->pivot->role === 'admin' || $group->creator_id === $user->id);
        }

        return false;
    }
}
