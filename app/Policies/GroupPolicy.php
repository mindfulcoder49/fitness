<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Group $group): bool
    {
        if ($group->is_public) {
            return true;
        }

        return $user->groups()->where('group_id', $group->id)->exists();
    }

    /**
     * Check if the user is an admin or creator of the group.
     */
    protected function isGroupAdmin(User $user, Group $group): bool
    {
        if ($user->id === $group->creator_id) {
            return true;
        }

        $membership = $user->groups()->where('group_id', $group->id)->first();
        return $membership && $membership->pivot->role === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Group $group): bool
    {
        return $this->isGroupAdmin($user, $group);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Group $group): bool
    {
        return $user->id === $group->creator_id;
    }

    /**
     * Determine whether the user can manage members in the group.
     */
    public function manageMembers(User $user, Group $group): bool
    {
        return $this->isGroupAdmin($user, $group);
    }
}
