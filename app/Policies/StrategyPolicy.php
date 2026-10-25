<?php

namespace App\Policies;

use App\Models\Strategy;
use App\Models\StrategyCollaborator;
use App\Models\TeamMember;
use App\Models\User;

class StrategyPolicy
{
    public function view(?User $user, Strategy $strategy): bool
    {
        if ($strategy->is_template && $strategy->status === 'active') {
            return true;
        }

        return $user ? $this->member($user, $strategy) || $this->collaborator($user, $strategy) : false;
    }

    public function update(User $user, Strategy $strategy): bool
    {
        if ($strategy->is_template || $strategy->status === 'archived') {
            return false;
        }

        return $this->manager($user, $strategy) || $this->collaborator($user, $strategy, 'edit');
    }

    public function archive(User $user, Strategy $strategy): bool
    {
        return ! $strategy->is_template && ($this->manager($user, $strategy) || $this->collaborator($user, $strategy, 'edit'));
    }

    public function share(User $user, Strategy $strategy): bool
    {
        return $this->archive($user, $strategy);
    }

    public function duplicate(?User $user, Strategy $strategy): bool
    {
        return $user !== null && $this->view($user, $strategy);
    }

    private function manager(User $user, Strategy $strategy): bool
    {
        if ($strategy->created_by === $user->id) {
            return true;
        }

        if (! $strategy->team_id) {
            return false;
        }

        return TeamMember::query()
            ->where('team_id', $strategy->team_id)
            ->where('user_id', $user->id)
            ->where('role', 'captain')
            ->exists();
    }

    private function member(User $user, Strategy $strategy): bool
    {
        if ($strategy->created_by === $user->id) {
            return true;
        }

        if (! $strategy->team_id) {
            return false;
        }

        return TeamMember::query()
            ->where('team_id', $strategy->team_id)
            ->where('user_id', $user->id)
            ->exists();
    }

    private function collaborator(User $user, Strategy $strategy, ?string $permission = null): bool
    {
        return StrategyCollaborator::query()
            ->where('strategy_id', $strategy->id)
            ->where('user_id', $user->id)
            ->when($permission, fn ($query) => $query->where('permission', $permission))
            ->exists();
    }
}
