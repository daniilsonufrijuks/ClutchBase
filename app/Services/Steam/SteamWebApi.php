<?php

namespace App\Services\Steam;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class SteamWebApi
{
    /**
     * Stat API names published in the CS2 game schema. Values are shown only when Steam returns them.
     *
     * @var list<string>
     */
    private const STAT_NAMES = [
        'total_kills',
        'total_deaths',
        'total_kills_headshot',
        'total_wins',
        'total_rounds_played',
        'total_damage_done',
        'total_planted_bombs',
        'total_defused_bombs',
        'total_mvps',
        'total_money_earned',
    ];

    public function configured(): bool
    {
        return filled(config('clutchbase.steam_web_api_key'));
    }

    /**
     * @return array{players: array<string, mixed>|null, news: list<array<string, mixed>>, achievements: list<array<string, mixed>>, errors: list<string>}
     */
    public function overview(): array
    {
        $errors = [];
        $players = $this->attempt('steam.players', 120, fn () => $this->currentPlayers(), $errors);
        $news = $this->attempt('steam.news', 600, fn () => $this->news(), $errors) ?? [];
        $achievements = $this->attempt('steam.achievements', 3600, fn () => $this->achievements(), $errors) ?? [];

        return [
            'players' => $players,
            'news' => $news,
            'achievements' => $achievements,
            'errors' => $errors,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function lookup(string $query): array
    {
        if (! $this->configured()) {
            return ['error' => 'Add STEAM_WEB_API_KEY on the server to look up a Steam profile.'];
        }

        $steamId = preg_match('/^\d{17}$/', $query) ? $query : $this->resolveVanity($query);
        if ($steamId === null) {
            return ['error' => 'Steam could not resolve that profile name.'];
        }

        return Cache::remember('steam.player.'.$steamId, 60, function () use ($steamId) {
            $player = $this->summary($steamId);
            if ($player === null) {
                return ['error' => 'Steam did not return that profile.'];
            }

            return [
                'error' => null,
                'player' => $player,
                'level' => $this->optional(fn () => $this->steamLevel($steamId)),
                'bans' => $this->optional(fn () => $this->bans($steamId)),
                'cs2' => $this->optional(fn () => $this->ownedCs2($steamId)),
                'friends' => $this->optional(fn () => $this->friends($steamId)),
                'recent_games' => $this->optional(fn () => $this->recentGames($steamId)) ?? [],
                'achievements' => $this->optional(fn () => $this->playerAchievements($steamId)),
                'stats' => $this->optional(fn () => $this->userStats($steamId)) ?? [],
            ];
        });
    }

    /**
     * @param  list<string>  $errors
     */
    private function attempt(string $key, int $seconds, callable $callback, array &$errors): mixed
    {
        try {
            return Cache::remember($key, $seconds, $callback);
        } catch (Throwable) {
            $errors[] = 'Steam did not return this data.';

            return null;
        }
    }

    /**
     * @return array{count: int, fetched_at: string}
     */
    private function currentPlayers(): array
    {
        $body = $this->get('ISteamUserStats/GetNumberOfCurrentPlayers/v1/', [
            'appid' => $this->appId(),
        ]);
        $response = $body['response'] ?? [];
        if (($response['result'] ?? 0) !== 1 || ! isset($response['player_count'])) {
            throw new \RuntimeException('Steam player count was missing.');
        }

        return [
            'count' => (int) $response['player_count'],
            'fetched_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return list<array{title: string, url: string, author: string|null, date: string|null, contents: string}>
     */
    private function news(): array
    {
        $body = $this->get('ISteamNews/GetNewsForApp/v2/', [
            'appid' => $this->appId(),
            'count' => 5,
            'maxlength' => 280,
        ]);
        $items = $body['appnews']['newsitems'] ?? [];
        if (! is_array($items)) {
            return [];
        }

        return collect($items)->map(function ($item) {
            if (! is_array($item) || empty($item['title']) || empty($item['url'])) {
                return null;
            }

            return [
                'title' => (string) $item['title'],
                'url' => (string) $item['url'],
                'author' => isset($item['author']) ? (string) $item['author'] : null,
                'date' => isset($item['date']) ? date('Y-m-d', (int) $item['date']) : null,
                'contents' => ltrim(trim(html_entity_decode(strip_tags((string) ($item['contents'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8')), '\\'),
            ];
        })->filter()->values()->all();
    }

    /**
     * @return list<array{name: string, percent: float}>
     */
    private function achievements(): array
    {
        $body = $this->get('ISteamUserStats/GetGlobalAchievementPercentagesForApp/v2/', [
            'gameid' => $this->appId(),
        ]);
        $rows = $body['achievementpercentages']['achievements'] ?? [];
        if (! is_array($rows)) {
            return [];
        }

        $names = $this->schema()['achievements'];

        return collect($rows)
            ->filter(fn ($row) => is_array($row) && isset($row['name'], $row['percent']))
            ->map(fn ($row) => [
                'name' => $names[$row['name']] ?? (string) $row['name'],
                'percent' => round((float) $row['percent'], 2),
            ])
            ->sortByDesc('percent')
            ->take(8)
            ->values()
            ->all();
    }

    /**
     * @return array{achievements: array<string, string>, stats: array<string, string>}
     */
    private function schema(): array
    {
        $empty = ['achievements' => [], 'stats' => []];
        if (! $this->configured()) {
            return $empty;
        }

        try {
            return Cache::remember('steam.schema.v2', 86400, function () use ($empty) {
                $body = $this->get('ISteamUserStats/GetSchemaForGame/v2/', [
                    'key' => $this->key(),
                    'appid' => $this->appId(),
                    'l' => 'english',
                ]);
                $stats = $body['game']['availableGameStats'] ?? [];
                if (! is_array($stats)) {
                    return $empty;
                }

                return [
                    'achievements' => $this->displayNames($stats['achievements'] ?? []),
                    'stats' => $this->displayNames($stats['stats'] ?? []),
                ];
            });
        } catch (Throwable) {
            return $empty;
        }
    }

    /**
     * @return array<string, string>
     */
    private function displayNames(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $names = [];
        foreach ($rows as $row) {
            if (is_array($row) && ! empty($row['name']) && ! empty($row['displayName'])) {
                $names[(string) $row['name']] = (string) $row['displayName'];
            }
        }

        return $names;
    }

    private function resolveVanity(string $vanity): ?string
    {
        $body = $this->get('ISteamUser/ResolveVanityURL/v1/', [
            'key' => $this->key(),
            'vanityurl' => $vanity,
        ]);
        $response = $body['response'] ?? [];
        if (($response['success'] ?? 0) !== 1 || empty($response['steamid'])) {
            return null;
        }

        return (string) $response['steamid'];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function summary(string $steamId): ?array
    {
        $body = $this->get('ISteamUser/GetPlayerSummaries/v2/', [
            'key' => $this->key(),
            'steamids' => $steamId,
        ]);
        $player = $body['response']['players'][0] ?? null;
        if (! is_array($player)) {
            return null;
        }

        $public = (int) ($player['communityvisibilitystate'] ?? 1) === 3;

        return [
            'steamid' => (string) ($player['steamid'] ?? $steamId),
            'name' => (string) ($player['personaname'] ?? 'Unknown'),
            'profile_url' => (string) ($player['profileurl'] ?? ''),
            'avatar' => (string) ($player['avatarfull'] ?? ''),
            'public' => $public,
            'created' => isset($player['timecreated']) ? date('Y-m-d', (int) $player['timecreated']) : null,
            'country' => isset($player['loccountrycode']) ? (string) $player['loccountrycode'] : null,
            'state' => $public ? $this->personaState((int) ($player['personastate'] ?? 0)) : null,
            'in_game' => $public && isset($player['gameextrainfo']) ? (string) $player['gameextrainfo'] : null,
        ];
    }

    private function personaState(int $state): string
    {
        return match ($state) {
            1 => 'online',
            2 => 'busy',
            3 => 'away',
            4 => 'snooze',
            5 => 'looking to trade',
            6 => 'looking to play',
            default => 'offline',
        };
    }

    private function steamLevel(string $steamId): ?int
    {
        $body = $this->get('IPlayerService/GetSteamLevel/v1/', [
            'key' => $this->key(),
            'steamid' => $steamId,
        ]);
        $level = $body['response']['player_level'] ?? null;

        return is_numeric($level) ? (int) $level : null;
    }

    /**
     * @return array{vac: bool, vac_bans: int, game_bans: int, community: bool, economy: string, days_since_last: int}|null
     */
    private function bans(string $steamId): ?array
    {
        $body = $this->get('ISteamUser/GetPlayerBans/v1/', [
            'key' => $this->key(),
            'steamids' => $steamId,
        ]);
        $player = $body['players'][0] ?? null;
        if (! is_array($player)) {
            return null;
        }

        return [
            'vac' => (bool) ($player['VACBanned'] ?? false),
            'vac_bans' => (int) ($player['NumberOfVACBans'] ?? 0),
            'game_bans' => (int) ($player['NumberOfGameBans'] ?? 0),
            'community' => (bool) ($player['CommunityBanned'] ?? false),
            'economy' => (string) ($player['EconomyBan'] ?? 'none'),
            'days_since_last' => (int) ($player['DaysSinceLastBan'] ?? 0),
        ];
    }

    /**
     * @return array{owns: bool, name?: string, playtime_forever?: int, playtime_2weeks?: int}|null
     */
    private function ownedCs2(string $steamId): ?array
    {
        $body = $this->get('IPlayerService/GetOwnedGames/v1/', [
            'key' => $this->key(),
            'steamid' => $steamId,
            'include_appinfo' => 1,
            'include_played_free_games' => 1,
            'appids_filter[0]' => $this->appId(),
        ]);
        $games = $body['response']['games'] ?? null;
        if (! is_array($games)) {
            return null;
        }

        $game = $games[0] ?? null;
        if (! is_array($game)) {
            return ['owns' => false];
        }

        return [
            'owns' => true,
            'name' => (string) ($game['name'] ?? 'Counter-Strike 2'),
            'playtime_forever' => (int) ($game['playtime_forever'] ?? 0),
            'playtime_2weeks' => (int) ($game['playtime_2weeks'] ?? 0),
        ];
    }

    /**
     * @return array{count: int, names: list<string>}|null
     */
    private function friends(string $steamId): ?array
    {
        $body = $this->get('ISteamUser/GetFriendList/v1/', [
            'key' => $this->key(),
            'steamid' => $steamId,
            'relationship' => 'friend',
        ]);
        $rows = $body['friendslist']['friends'] ?? null;
        if (! is_array($rows)) {
            return null;
        }

        $ids = collect($rows)->pluck('steamid')->filter()->take(8)->implode(',');
        $names = [];
        if ($ids !== '') {
            $summaries = $this->get('ISteamUser/GetPlayerSummaries/v2/', [
                'key' => $this->key(),
                'steamids' => $ids,
            ]);
            $players = $summaries['response']['players'] ?? [];
            if (is_array($players)) {
                $names = collect($players)->pluck('personaname')->filter()->map(fn ($name) => (string) $name)->values()->all();
            }
        }

        return [
            'count' => count($rows),
            'names' => $names,
        ];
    }

    /**
     * @return list<array{name: string, value: int}>
     */
    private function userStats(string $steamId): array
    {
        $body = $this->get('ISteamUserStats/GetUserStatsForGame/v2/', [
            'key' => $this->key(),
            'steamid' => $steamId,
            'appid' => $this->appId(),
        ]);
        $rows = $body['playerstats']['stats'] ?? null;
        if (! is_array($rows)) {
            return [];
        }

        $labels = $this->schema()['stats'];
        $mapped = collect($rows)
            ->filter(fn ($row) => is_array($row) && isset($row['name'], $row['value']) && is_numeric($row['value']))
            ->map(fn ($row) => [
                'api_name' => (string) $row['name'],
                'name' => $labels[$row['name']] ?? (string) $row['name'],
                'value' => (int) $row['value'],
            ]);
        $preferred = $mapped
            ->filter(fn ($row) => in_array($row['api_name'], self::STAT_NAMES, true))
            ->sortBy(fn ($row) => array_search($row['api_name'], self::STAT_NAMES, true));
        $shown = $preferred->isNotEmpty() ? $preferred : $mapped->take(8);

        return $shown->map(fn ($row) => ['name' => $row['name'], 'value' => $row['value']])->values()->all();
    }

    private function optional(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentGames(string $steamId): array
    {
        $body = $this->get('IPlayerService/GetRecentlyPlayedGames/v1/', [
            'key' => $this->key(),
            'steamid' => $steamId,
        ]);
        $games = $body['response']['games'] ?? [];
        if (! is_array($games)) {
            return [];
        }

        return collect($games)->take(5)->map(fn ($game) => [
            'appid' => (int) ($game['appid'] ?? 0),
            'name' => (string) ($game['name'] ?? 'Unknown'),
            'playtime_2weeks' => (int) ($game['playtime_2weeks'] ?? 0),
            'playtime_forever' => (int) ($game['playtime_forever'] ?? 0),
        ])->all();
    }

    /**
     * @return array{unlocked: int, total: int}|null
     */
    private function playerAchievements(string $steamId): ?array
    {
        $body = $this->get('ISteamUserStats/GetPlayerAchievements/v1/', [
            'key' => $this->key(),
            'steamid' => $steamId,
            'appid' => $this->appId(),
        ]);
        $stats = $body['playerstats'] ?? [];
        if (($stats['success'] ?? false) !== true || ! is_array($stats['achievements'] ?? null)) {
            return null;
        }

        $rows = $stats['achievements'];

        return [
            'unlocked' => collect($rows)->where('achieved', 1)->count(),
            'total' => count($rows),
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $method, array $query): array
    {
        $response = Http::timeout(8)
            ->acceptJson()
            ->get('https://api.steampowered.com/'.$method, $query);
        $response->throw();
        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    private function appId(): int
    {
        return (int) config('clutchbase.steam_app_id', 730);
    }

    private function key(): string
    {
        return (string) config('clutchbase.steam_web_api_key');
    }
}
