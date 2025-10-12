<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Post $post): bool
    {
        return $user->role === 'admin' || $user->is($post->user);
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->role === 'admin' || $user->is($post->user);
    }
}
