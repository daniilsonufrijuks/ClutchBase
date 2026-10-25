<?php

namespace App\Services\Accounts;

use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\Wishlist;
use App\Support\Slugs;
use Illuminate\Support\Facades\DB;

class AccountProvisioner
{
    public function provision(User $user): Team
    {
        return DB::transaction(function () use ($user) {
            $team = Team::query()->create([
                'owner_id' => $user->id,
                'name' => $user->name."'s team",
                'slug' => Slugs::unique(Team::class, $user->name.' team'),
            ]);

            TeamMember::query()->create([
                'team_id' => $team->id,
                'user_id' => $user->id,
                'role' => 'captain',
            ]);

            Wishlist::query()->create([
                'user_id' => $user->id,
                'name' => 'Wishlist',
            ]);

            $this->acceptInvites($user);

            return $team;
        });
    }

    public function acceptInvites(User $user): void
    {
        $invites = TeamInvite::query()
            ->whereRaw('lower(email) = ?', [mb_strtolower($user->email)])
            ->whereNull('accepted_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->get();

        foreach ($invites as $invite) {
            TeamMember::query()->firstOrCreate(
                ['team_id' => $invite->team_id, 'user_id' => $user->id],
                ['role' => $invite->role],
            );
            $invite->update(['accepted_at' => now()]);
        }
    }
}
