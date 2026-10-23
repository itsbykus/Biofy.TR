<?php

namespace App\Policies;

use App\Models\Profile;
use App\Models\User;

/** Object-level access control: a page (and everything under it) belongs to exactly one user. */
class ProfilePolicy
{
    public function view(User $user, Profile $profile): bool
    {
        return $user->id === $profile->user_id;
    }

    public function update(User $user, Profile $profile): bool
    {
        return $user->id === $profile->user_id && $user->isActive() && $profile->status !== Profile::STATUS_SUSPENDED;
    }

    public function publish(User $user, Profile $profile): bool
    {
        return $this->update($user, $profile);
    }

    public function delete(User $user, Profile $profile): bool
    {
        return $user->id === $profile->user_id;
    }
}
