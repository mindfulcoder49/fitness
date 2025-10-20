<?php

namespace App\Policies;

use App\Models\Meetup;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MeetupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Meetup $meetup): bool
    {
        return $user->is_admin || $meetup->group->isUserAdmin($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function destroy(User $user, Meetup $meetup): bool
    {
        return $user->is_admin || $meetup->group->isUserAdmin($user);
    }
}
