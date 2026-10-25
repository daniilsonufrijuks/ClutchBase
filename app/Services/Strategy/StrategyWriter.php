<?php

namespace App\Services\Strategy;

use App\Models\Strategy;
use App\Models\StrategyMarker;
use App\Models\StrategyPath;
use App\Models\StrategyPlayer;
use App\Models\StrategyShare;
use App\Models\StrategyTimelineStep;
use App\Models\StrategyUtilityAction;
use App\Models\StrategyVersion;
use App\Models\Team;
use App\Models\User;
use App\Support\Slugs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StrategyWriter
{
    public function create(User $user, array $data): Strategy
    {
        return DB::transaction(function () use ($user, $data) {
            $strategy = Strategy::query()->create([
                'team_id' => $data['team_id'],
                'map_asset_id' => $data['map_asset_id'],
                'folder_id' => $data['folder_id'] ?? null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'title' => $data['title'],
                'slug' => Slugs::unique(Strategy::class, $data['title']),
                'side' => $data['side'],
                'objective' => $data['objective'],
                'summary' => $data['summary'] ?? 'Example timings are editable and are not verified lineups.',
                'template_key' => $data['template_key'] ?? null,
                'is_template' => false,
                'status' => 'active',
                'visibility' => $data['visibility'] ?? 'private',
                'revision' => 1,
            ]);

            if (! empty($data['template_id'])) {
                $template = Strategy::query()->where('is_template', true)->findOrFail($data['template_id']);
                $this->copyChildren($template, $strategy);
                $strategy->update(['template_key' => $template->template_key]);
            } else {
                $this->seedPlayers($strategy, $data['side']);
            }

            return $strategy->fresh(['players', 'markers', 'paths', 'utilityActions', 'timelineSteps', 'map']);
        });
    }

    public function save(Strategy $strategy, User $user, array $data): Strategy
    {
        return DB::transaction(function () use ($strategy, $user, $data) {
            $locked = Strategy::query()->lockForUpdate()->findOrFail($strategy->id);

            if ((int) $locked->revision !== (int) $data['revision']) {
                throw new StrategyConflictException('Revision mismatch.');
            }

            $locked->fill([
                'title' => $data['title'],
                'map_asset_id' => $data['map_asset_id'],
                'folder_id' => $data['folder_id'] ?? null,
                'side' => $data['side'],
                'objective' => $data['objective'],
                'summary' => $data['summary'] ?? null,
                'visibility' => $data['visibility'],
                'updated_by' => $user->id,
                'revision' => $locked->revision + 1,
            ])->save();

            $this->replaceChildren($locked, $data);

            StrategyVersion::query()->create([
                'strategy_id' => $locked->id,
                'revision' => $locked->revision,
                'user_id' => $user->id,
                'snapshot' => $data,
                'created_at' => now(),
            ]);

            return $locked->fresh(['players', 'markers', 'paths', 'utilityActions', 'timelineSteps', 'map']);
        });
    }

    public function duplicate(Strategy $source, User $user, Team $team): Strategy
    {
        return DB::transaction(function () use ($source, $user, $team) {
            $source->load(['players', 'markers', 'paths', 'utilityActions', 'timelineSteps']);

            $copy = $source->replicate([
                'slug', 'revision', 'is_template', 'created_by', 'updated_by',
            ]);
            $copy->title = 'Copy of '.$source->title;
            $copy->slug = Slugs::unique(Strategy::class, $copy->title);
            $copy->team_id = $team->id;
            $copy->folder_id = null;
            $copy->created_by = $user->id;
            $copy->updated_by = $user->id;
            $copy->is_template = false;
            $copy->visibility = 'private';
            $copy->status = 'active';
            $copy->revision = 1;
            $copy->save();
            $this->copyChildren($source, $copy);

            return $copy;
        });
    }

    public function share(Strategy $strategy, User $user, string $permission, ?string $expiresAt): array
    {
        $token = Str::random(48);

        StrategyShare::query()->create([
            'strategy_id' => $strategy->id,
            'token_hash' => hash('sha256', $token),
            'permission' => $permission,
            'expires_at' => $expiresAt,
            'created_by' => $user->id,
        ]);

        return [
            'url' => url('/s/'.$token),
            'permission' => $permission,
        ];
    }

    public function findShare(string $token): ?StrategyShare
    {
        $share = StrategyShare::query()->where('token_hash', hash('sha256', $token))->first();

        if (! $share || ! $share->isUsable()) {
            return null;
        }

        return $share;
    }

    public function graph(Strategy $strategy): array
    {
        $strategy->loadMissing(['map', 'players', 'markers', 'paths', 'utilityActions', 'timelineSteps', 'creator', 'editor']);

        return [
            'id' => $strategy->id,
            'revision' => $strategy->revision,
            'title' => $strategy->title,
            'slug' => $strategy->slug,
            'side' => $strategy->side,
            'objective' => $strategy->objective,
            'summary' => $strategy->summary,
            'visibility' => $strategy->visibility,
            'status' => $strategy->status,
            'is_template' => $strategy->is_template,
            'folder_id' => $strategy->folder_id,
            'team_id' => $strategy->team_id,
            'updated_at' => $strategy->updated_at?->toIso8601String(),
            'creator' => $strategy->creator?->name,
            'editor' => $strategy->editor?->name,
            'map' => [
                'id' => $strategy->map->id,
                'name' => $strategy->map->name,
                'slug' => $strategy->map->slug,
                'image' => $strategy->map->image_path,
                'license' => $strategy->map->license_note,
            ],
            'players' => $strategy->players->map(fn (StrategyPlayer $player) => [
                'slot' => $player->slot,
                'nickname' => $player->nickname,
                'role' => $player->role,
                'start_x' => $this->unit($player->start_x),
                'start_y' => $this->unit($player->start_y),
                'responsibilities' => $player->responsibilities,
                'contingency' => $player->contingency,
            ])->values()->all(),
            'markers' => $strategy->markers->map(fn (StrategyMarker $marker) => [
                'kind' => $marker->kind,
                'player_slot' => $marker->player_slot,
                'x' => $this->unit($marker->x),
                'y' => $this->unit($marker->y),
                'label' => $marker->label,
                'step_number' => $marker->step_number,
            ])->values()->all(),
            'paths' => $strategy->paths->map(fn (StrategyPath $path) => [
                'player_slot' => $path->player_slot,
                'label' => $path->label,
                'points' => $path->points,
            ])->values()->all(),
            'utility' => $strategy->utilityActions->map(fn (StrategyUtilityAction $action) => [
                'player_slot' => $action->player_slot,
                'utility_type' => $action->utility_type,
                'throw_x' => $this->unit($action->throw_x),
                'throw_y' => $this->unit($action->throw_y),
                'land_x' => $this->unit($action->land_x),
                'land_y' => $this->unit($action->land_y),
                'timing_seconds' => $action->timing_seconds,
                'step_number' => $action->step_number,
                'notes' => $action->notes,
                'lineup_reference' => $action->lineup_reference,
            ])->values()->all(),
            'timeline' => $strategy->timelineSteps->map(fn (StrategyTimelineStep $step) => [
                'seconds' => $step->seconds,
                'title' => $step->title,
                'instructions' => $step->instructions,
            ])->values()->all(),
            'disclaimer' => config('clutchbase.disclaimers.strategy'),
        ];
    }

    private function replaceChildren(Strategy $strategy, array $data): void
    {
        $strategy->players()->delete();
        $strategy->markers()->delete();
        $strategy->paths()->delete();
        $strategy->utilityActions()->delete();
        $strategy->timelineSteps()->delete();

        foreach ($data['players'] as $player) {
            $strategy->players()->create($player);
        }
        foreach ($data['markers'] ?? [] as $index => $marker) {
            $strategy->markers()->create($marker + ['sort_order' => $index]);
        }
        foreach ($data['paths'] ?? [] as $path) {
            $strategy->paths()->create($path);
        }
        foreach ($data['utility'] ?? [] as $action) {
            $strategy->utilityActions()->create($action);
        }
        foreach ($data['timeline'] ?? [] as $index => $step) {
            $strategy->timelineSteps()->create($step + ['sort_order' => $index]);
        }
    }

    private function copyChildren(Strategy $source, Strategy $target): void
    {
        $source->loadMissing(['players', 'markers', 'paths', 'utilityActions', 'timelineSteps']);

        foreach ($source->players as $player) {
            $target->players()->create($player->only([
                'slot', 'nickname', 'role', 'start_x', 'start_y', 'responsibilities', 'contingency',
            ]));
        }
        foreach ($source->markers as $marker) {
            $target->markers()->create($marker->only([
                'kind', 'player_slot', 'x', 'y', 'label', 'step_number', 'sort_order',
            ]));
        }
        foreach ($source->paths as $path) {
            $target->paths()->create($path->only(['player_slot', 'label', 'points']));
        }
        foreach ($source->utilityActions as $action) {
            $target->utilityActions()->create($action->only([
                'player_slot', 'utility_type', 'throw_x', 'throw_y', 'land_x', 'land_y',
                'timing_seconds', 'step_number', 'notes', 'lineup_reference',
            ]));
        }
        foreach ($source->timelineSteps as $step) {
            $target->timelineSteps()->create($step->only(['seconds', 'title', 'instructions', 'sort_order']));
        }
    }

    private function seedPlayers(Strategy $strategy, string $side): void
    {
        $roles = array_keys(config('strategy.roles.'.$side, config('strategy.roles.t')));

        foreach (range(1, 5) as $slot) {
            $strategy->players()->create([
                'slot' => $slot,
                'nickname' => 'Player '.$slot,
                'role' => $roles[$slot - 1] ?? 'support',
            ]);
        }
    }

    private function unit(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }
}
