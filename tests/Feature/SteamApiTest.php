<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SteamApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_steam_page_shows_documented_stats_without_exposing_the_key(): void
    {
        config(['clutchbase.steam_web_api_key' => 'server-secret']);
        Http::fake([
            'api.steampowered.com/ISteamUserStats/GetNumberOfCurrentPlayers/*' => Http::response([
                'response' => ['player_count' => 812345, 'result' => 1],
            ]),
            'api.steampowered.com/ISteamNews/GetNewsForApp/*' => Http::response([
                'appnews' => ['newsitems' => [[
                    'title' => 'Patch notes',
                    'url' => 'https://steamcommunity.com/games/730/announcements/detail/1',
                    'author' => 'Valve',
                    'date' => 1710000000,
                    'contents' => '<b>Notes</b>',
                ]]],
            ]),
            'api.steampowered.com/ISteamUserStats/GetGlobalAchievementPercentagesForApp/*' => Http::response([
                'achievementpercentages' => ['achievements' => [
                    ['name' => 'WIN_ROUNDS', 'percent' => 40.5],
                    ['name' => 'PLAY_ROUNDS', 'percent' => 90.2],
                ]],
            ]),
            'api.steampowered.com/ISteamUserStats/GetSchemaForGame/*' => Http::response([
                'game' => ['availableGameStats' => [
                    'achievements' => [
                        ['name' => 'PLAY_ROUNDS', 'displayName' => 'Play rounds'],
                    ],
                    'stats' => [
                        ['name' => 'total_kills', 'displayName' => 'Total Kills'],
                        ['name' => 'total_rounds_played', 'displayName' => 'Total Rounds'],
                    ],
                ]],
            ]),
            'api.steampowered.com/ISteamUser/ResolveVanityURL/*' => Http::response([
                'response' => ['success' => 1, 'steamid' => '76561198000000000'],
            ]),
            'api.steampowered.com/ISteamUser/GetPlayerSummaries/*' => Http::response([
                'response' => ['players' => [[
                    'steamid' => '76561198000000000',
                    'personaname' => 'Example',
                    'profileurl' => 'https://steamcommunity.com/id/example',
                    'avatarfull' => 'https://example.test/avatar.jpg',
                    'communityvisibilitystate' => 3,
                    'personastate' => 1,
                    'timecreated' => 1400000000,
                    'loccountrycode' => 'US',
                    'gameextrainfo' => 'Counter-Strike 2',
                ]]],
            ]),
            'api.steampowered.com/IPlayerService/GetSteamLevel/*' => Http::response([
                'response' => ['player_level' => 42],
            ]),
            'api.steampowered.com/ISteamUser/GetPlayerBans/*' => Http::response([
                'players' => [[
                    'VACBanned' => false,
                    'NumberOfVACBans' => 0,
                    'NumberOfGameBans' => 1,
                    'CommunityBanned' => false,
                    'EconomyBan' => 'none',
                    'DaysSinceLastBan' => 10,
                ]],
            ]),
            'api.steampowered.com/IPlayerService/GetOwnedGames/*' => Http::response([
                'response' => ['games' => [[
                    'appid' => 730,
                    'name' => 'Counter-Strike 2',
                    'playtime_forever' => 900,
                    'playtime_2weeks' => 30,
                ]]],
            ]),
            'api.steampowered.com/ISteamUser/GetFriendList/*' => Http::response([
                'friendslist' => ['friends' => [
                    ['steamid' => '76561198000000001'],
                ]],
            ]),
            'api.steampowered.com/ISteamUserStats/GetUserStatsForGame/*' => Http::response([
                'playerstats' => ['stats' => [
                    ['name' => 'total_kills', 'value' => 1200],
                    ['name' => 'total_rounds_played', 'value' => 80],
                    ['name' => 'unused_stat', 'value' => 1],
                ]],
            ]),
            'api.steampowered.com/IPlayerService/GetRecentlyPlayedGames/*' => Http::response([
                'response' => ['games' => [[
                    'appid' => 730,
                    'name' => 'Counter-Strike 2',
                    'playtime_2weeks' => 20,
                    'playtime_forever' => 400,
                ]]],
            ]),
            'api.steampowered.com/ISteamUserStats/GetPlayerAchievements/*' => Http::response([
                'playerstats' => ['success' => true, 'achievements' => [
                    ['apiname' => 'PLAY_ROUNDS', 'achieved' => 1],
                    ['apiname' => 'WIN_ROUNDS', 'achieved' => 0],
                ]],
            ]),
        ]);

        $this->get('/steam')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.players.count', 812345)
                ->where('stats.news.0.title', 'Patch notes')
                ->where('stats.achievements.0.name', 'Play rounds')
                ->where('configured', true)
            )
            ->assertDontSee('server-secret');

        $this->get('/steam?q=example')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('lookup.player.name', 'Example')
                ->where('lookup.player.state', 'online')
                ->where('lookup.level', 42)
                ->where('lookup.bans.game_bans', 1)
                ->where('lookup.cs2.playtime_forever', 900)
                ->where('lookup.friends.count', 1)
                ->where('lookup.stats.0.name', 'Total Kills')
                ->where('lookup.stats.0.value', 1200)
                ->where('lookup.achievements.unlocked', 1)
                ->where('lookup.recent_games.0.name', 'Counter-Strike 2')
                ->where('seo.robots', 'noindex,follow')
            )
            ->assertDontSee('server-secret');
    }

    public function test_profile_lookup_explains_a_missing_key(): void
    {
        config(['clutchbase.steam_web_api_key' => null]);
        Http::fake([
            'api.steampowered.com/*' => Http::response([], 500),
        ]);

        $this->get('/steam?q=example')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('lookup.error', 'Add STEAM_WEB_API_KEY on the server to look up a Steam profile.'));
    }
}
