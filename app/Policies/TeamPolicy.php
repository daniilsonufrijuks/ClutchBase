<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;

class TeamPolicy
{
    public function view(User $user, Team $team): bool
    {
        return $this->member($user, $team);
    }

    public function manage(User $user, Team $team): bool
    {
        return $team->owner_id === $user->id || TeamMember::query()
            ->where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->where('role', 'captain')
            ->exists();
    }

    private function member(User $user, Team $team): bool
    {
        return TeamMember::query()->where('team_id', $team->id)->where('user_id', $user->id)->exists();
    }
}
