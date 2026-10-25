<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\MapAsset;
use App\Models\Strategy;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\Accounts\AccountProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StrategyTest extends TestCase
{
    use RefreshDatabase;

    public function test_captain_can_save_and_reload_a_five_player_plan(): void
    {
        [$user, $map] = $this->captain();
        $strategy = $this->createStrategy($user, $map);

        $this->actingAs($user)->put('/strategies/'.$strategy->id, $this->payload($strategy, [
            'title' => 'A execute saved',
            'players' => $this->players(),
            'markers' => [['kind' => 'smoke', 'player_slot' => 1, 'x' => 0.4, 'y' => 0.3, 'label' => 'Smoke', 'step_number' => 1]],
            'paths' => [['player_slot' => 1, 'label' => 'Entry', 'points' => [['x' => 0.2, 'y' => 0.8], ['x' => 0.5, 'y' => 0.4]]]],
            'utility' => [[
                'player_slot' => 1, 'utility_type' => 'smoke', 'throw_x' => 0.2, 'throw_y' => 0.8,
                'land_x' => 0.4, 'land_y' => 0.3, 'timing_seconds' => 15, 'step_number' => 1,
                'notes' => 'Example', 'lineup_reference' => null,
            ]],
            'timeline' => [['seconds' => 15, 'title' => 'Smoke', 'instructions' => 'Editable example']],
        ]))->assertRedirect();

        $this->actingAs($user)->get('/strategies/'.$strategy->id)->assertOk()->assertInertia(fn ($page) => $page
            ->where('strategy.title', 'A execute saved')
            ->where('strategy.revision', 2)
            ->where('strategy.players', fn ($players) => count($players) === 5)
            ->where('strategy.markers.0.kind', 'smoke')
            ->where('strategy.paths.0.points.1.x', 0.5)
            ->where('strategy.utility.0.timing_seconds', 15)
        );
    }

    public function test_outsiders_cannot_edit_and_viewers_cannot_change_a_private_plan(): void
    {
        [$user, $map] = $this->captain();
        $strategy = $this->createStrategy($user, $map);
        $stranger = User::factory()->create();
        app(AccountProvisioner::class)->provision($stranger);

        $this->actingAs($stranger)->get('/strategies/'.$strategy->id)->assertForbidden();
        $this->actingAs($stranger)->put('/strategies/'.$strategy->id, $this->payload($strategy))->assertForbidden();

        $mate = User::factory()->create();
        TeamMember::query()->create(['team_id' => $strategy->team_id, 'user_id' => $mate->id, 'role' => 'member']);
        $this->actingAs($mate)->get('/strategies/'.$strategy->id)->assertOk();
        $this->actingAs($mate)->put('/strategies/'.$strategy->id, $this->payload($strategy, ['title' => 'Hijack']))->assertForbidden();
    }

    public function test_share_links_can_be_viewed_and_revoked(): void
    {
        [$user, $map] = $this->captain();
        $strategy = $this->createStrategy($user, $map);

        $this->actingAs($user)->post('/strategies/'.$strategy->id.'/shares', ['permission' => 'view'])->assertRedirect();
        $url = session('share_url');
        $token = basename((string) $url);

        $this->get('/s/'.$token)->assertOk()->assertInertia(fn ($page) => $page
            ->component('Strategy/Show')
            ->where('strategy.title', $strategy->title)
            ->where('seo.robots', 'noindex,nofollow')
        );
        $this->get('/s/'.$token)->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $share = $strategy->shares()->first();
        $this->actingAs($user)->delete('/strategy-shares/'.$share->id)->assertRedirect();
        $this->get('/s/'.$token)->assertNotFound();
    }

    public function test_stale_revision_is_rejected(): void
    {
        [$user, $map] = $this->captain();
        $strategy = $this->createStrategy($user, $map);
        $payload = $this->payload($strategy, ['title' => 'First']);
        $this->actingAs($user)->put('/strategies/'.$strategy->id, $payload)->assertRedirect();

        $this->actingAs($user)->putJson('/strategies/'.$strategy->id, $payload)->assertStatus(409);
    }

    public function test_coordinates_outside_the_map_are_rejected(): void
    {
        [$user, $map] = $this->captain();
        $strategy = $this->createStrategy($user, $map);

        $this->actingAs($user)->put('/strategies/'.$strategy->id, $this->payload($strategy, [
            'markers' => [['kind' => 'smoke', 'player_slot' => 1, 'x' => 1.4, 'y' => 0.2, 'label' => 'Bad', 'step_number' => 1]],
        ]))->assertSessionHasErrors('markers.0.x');
    }

    public function test_duplicate_archive_and_export(): void
    {
        [$user, $map] = $this->captain();
        $strategy = $this->createStrategy($user, $map);

        $this->actingAs($user)->post('/strategies/'.$strategy->id.'/duplicate')->assertRedirect();
        $this->assertSame(2, Strategy::query()->where('is_template', false)->count());

        $this->actingAs($user)->post('/strategies/'.$strategy->id.'/archive')->assertRedirect();
        $this->assertSame('archived', $strategy->fresh()->status);

        $this->actingAs($user)->get('/strategies/'.$strategy->id.'/export')
            ->assertOk()
            ->assertJsonPath('strategy.title', $strategy->title)
            ->assertJsonPath('disclaimer', config('clutchbase.disclaimers.strategy'));
    }

    public function test_strategies_are_absent_from_the_sitemap(): void
    {
        [$user, $map] = $this->captain();
        $strategy = $this->createStrategy($user, $map);
        $item = Item::factory()->create();

        $this->get('/sitemap.xml')->assertOk()
            ->assertSee(url($item->canonicalPath()), false)
            ->assertDontSee('/strategies/'.$strategy->id, false);
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /strategies');
    }

    private function captain(): array
    {
        $user = User::factory()->create();
        app(AccountProvisioner::class)->provision($user);
        $map = MapAsset::query()->create([
            'name' => 'Test schematic',
            'slug' => 'test-schematic',
            'image_path' => '/media/maps/test.svg',
            'license_note' => 'Original test diagram.',
        ]);

        return [$user, $map];
    }

    private function createStrategy(User $user, MapAsset $map): Strategy
    {
        $teamId = $user->memberships()->first()->team_id;

        $this->actingAs($user)->post('/strategies', [
            'title' => 'A execute',
            'map_asset_id' => $map->id,
            'team_id' => $teamId,
            'side' => 't',
            'objective' => 'a_execute',
        ])->assertRedirect();

        return Strategy::query()->where('title', 'A execute')->where('team_id', $teamId)->firstOrFail();
    }

    private function payload(Strategy $strategy, array $overrides = []): array
    {
        return array_merge([
            'revision' => $strategy->fresh()->revision,
            'title' => $strategy->title,
            'map_asset_id' => $strategy->map_asset_id,
            'folder_id' => null,
            'side' => 't',
            'objective' => 'a_execute',
            'summary' => 'Notes',
            'visibility' => 'private',
            'players' => $this->players(),
            'markers' => [],
            'paths' => [],
            'utility' => [],
            'timeline' => [],
        ], $overrides);
    }

    private function players(): array
    {
        return collect(range(1, 5))->map(fn ($slot) => [
            'slot' => $slot,
            'nickname' => 'Player '.$slot,
            'role' => 'support',
            'start_x' => 0.2,
            'start_y' => 0.8,
            'responsibilities' => 'Hold',
            'contingency' => 'Fall back',
        ])->all();
    }
}
