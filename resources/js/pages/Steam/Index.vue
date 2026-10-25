<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });

defineProps<{
    configured: boolean;
    query: string;
    stats: {
        players: { count: number; fetched_at: string } | null;
        news: { title: string; url: string; author: string | null; date: string | null; contents: string }[];
        achievements: { name: string; percent: number }[];
        errors: string[];
    };
    lookup: {
        error: string | null;
        player?: {
            steamid: string; name: string; profile_url: string; avatar: string; public: boolean;
            created: string | null; country: string | null; state: string | null; in_game: string | null;
        };
        level?: number | null;
        bans?: { vac: boolean; vac_bans: number; game_bans: number; community: boolean; economy: string; days_since_last: number } | null;
        cs2?: { owns: boolean; name?: string; playtime_forever?: number; playtime_2weeks?: number } | null;
        friends?: { count: number; names: string[] } | null;
        recent_games?: { appid: number; name: string; playtime_2weeks: number; playtime_forever: number }[];
        achievements?: { unlocked: number; total: number } | null;
        stats?: { name: string; value: number }[];
    } | null;
    seo: SeoMeta;
}>();

function search(event: Event) {
    const data = new FormData(event.target as HTMLFormElement);
    router.get('/steam', { q: String(data.get('q') || '') }, { preserveState: true });
}
</script>

<template>
    <SeoHead :seo="seo" />
    <h1 class="font-display text-3xl">CS2 on Steam</h1>
    <p class="mt-2 max-w-2xl text-sm text-muted">
        Current players, news, and achievement percentages come from the documented Steamworks Web API for app 730. These are not market prices.
    </p>
    <p v-if="stats.errors.length" class="mt-3 text-sm text-muted" role="status">Some Steam calls did not return data.</p>

    <section class="mt-6 rounded-xl border border-line bg-panel p-4">
        <h2 class="font-display text-xl">Players in game</h2>
        <p v-if="stats.players" class="mt-2 font-display text-4xl">{{ stats.players.count.toLocaleString() }}</p>
        <p v-else class="mt-2 text-sm text-muted">Player count is unavailable.</p>
        <p v-if="stats.players" class="mt-1 text-xs text-muted">From GetNumberOfCurrentPlayers. Observed {{ stats.players.fetched_at }}.</p>
    </section>

    <section class="mt-6">
        <h2 class="font-display text-xl">Global achievements</h2>
        <p v-if="!stats.achievements.length" class="mt-2 text-sm text-muted">Achievement percentages are unavailable.</p>
        <ul v-else class="mt-3 space-y-2">
            <li v-for="row in stats.achievements" :key="row.name" class="grid grid-cols-[1fr_4rem] gap-3 text-sm">
                <span>{{ row.name }}</span>
                <span class="text-right text-muted">{{ row.percent }}%</span>
            </li>
        </ul>
        <p class="mt-2 text-xs text-muted">From GetGlobalAchievementPercentagesForApp. Display names use GetSchemaForGame when a server API key is set.</p>
    </section>

    <section class="mt-6">
        <h2 class="font-display text-xl">Steam news</h2>
        <ul class="mt-3 space-y-4">
            <li v-for="item in stats.news" :key="item.url" class="rounded-xl border border-line p-4">
                <a :href="item.url" class="font-display text-lg" rel="noreferrer">{{ item.title }}</a>
                <p class="text-xs text-muted">{{ item.date }} <span v-if="item.author">· {{ item.author }}</span></p>
                <p class="mt-2 text-sm text-muted">{{ item.contents }}</p>
            </li>
        </ul>
        <p v-if="!stats.news.length" class="mt-2 text-sm text-muted">News is unavailable.</p>
    </section>

    <section class="mt-8 rounded-xl border border-line bg-panel p-4">
        <h2 class="font-display text-xl">Profile lookup</h2>
        <p class="mt-2 text-sm text-muted">Uses ResolveVanityURL, GetPlayerSummaries, GetSteamLevel, GetPlayerBans, GetOwnedGames, GetFriendList, GetRecentlyPlayedGames, GetPlayerAchievements, and GetUserStatsForGame. The API key stays on the server.</p>
        <p v-if="!configured" class="mt-2 text-sm">Set STEAM_WEB_API_KEY before looking up a profile. Public stats above do not need it.</p>
        <form class="mt-3 flex flex-wrap gap-2" @submit.prevent="search">
            <label class="sr-only" for="steam-q">SteamID64 or profile name</label>
            <input id="steam-q" name="q" :value="query" class="min-w-64 flex-1 rounded border border-line bg-ink px-3 py-2" placeholder="SteamID64 or vanity name" />
            <button class="rounded bg-brass px-4 py-2 text-ink" type="submit">Look up</button>
        </form>
        <p v-if="lookup?.error" class="mt-3 text-sm" role="alert">{{ lookup.error }}</p>
        <div v-else-if="lookup?.player" class="mt-4 flex gap-4">
            <img v-if="lookup.player.avatar" :src="lookup.player.avatar" alt="" class="h-16 w-16 rounded" width="64" height="64" />
            <div>
                <a v-if="lookup.player.profile_url" :href="lookup.player.profile_url" class="font-display text-xl" rel="noreferrer">{{ lookup.player.name }}</a>
                <p v-else class="font-display text-xl">{{ lookup.player.name }}</p>
                <p class="text-sm text-muted">
                    {{ lookup.player.steamid }}
                    · {{ lookup.player.public ? 'public profile' : 'profile is not public' }}
                    <span v-if="lookup.player.state"> · {{ lookup.player.state }}</span>
                    <span v-if="lookup.player.in_game"> · in {{ lookup.player.in_game }}</span>
                </p>
                <p v-if="lookup.level !== null && lookup.level !== undefined" class="text-sm">Steam level {{ lookup.level }}</p>
                <p v-if="lookup.player.created" class="text-sm text-muted">Account created {{ lookup.player.created }}<span v-if="lookup.player.country"> · {{ lookup.player.country }}</span></p>
                <p v-if="lookup.achievements" class="text-sm">CS2 achievements unlocked: {{ lookup.achievements.unlocked }} / {{ lookup.achievements.total }}</p>
                <p v-else class="text-sm text-muted">CS2 achievements are hidden or unavailable.</p>
                <p v-if="lookup.cs2?.owns" class="text-sm">{{ lookup.cs2.name }} owned · {{ lookup.cs2.playtime_forever }} minutes total · {{ lookup.cs2.playtime_2weeks }} minutes in two weeks</p>
                <p v-else-if="lookup.cs2 && !lookup.cs2.owns" class="text-sm text-muted">CS2 is not in the visible game library.</p>
                <p v-if="lookup.bans" class="text-sm">
                    VAC bans {{ lookup.bans.vac_bans }} · game bans {{ lookup.bans.game_bans }} · community ban {{ lookup.bans.community ? 'yes' : 'no' }} · economy {{ lookup.bans.economy }}
                </p>
            </div>
        </div>
        <div v-if="lookup?.stats?.length" class="mt-4">
            <h3 class="font-display">CS2 stats</h3>
            <ul class="mt-2 space-y-1 text-sm">
                <li v-for="stat in lookup.stats" :key="stat.name" class="flex justify-between gap-4">
                    <span>{{ stat.name }}</span>
                    <span class="text-muted">{{ stat.value.toLocaleString() }}</span>
                </li>
            </ul>
        </div>
        <p v-if="lookup?.friends" class="mt-4 text-sm">{{ lookup.friends.count }} public friends<span v-if="lookup.friends.names.length"> · {{ lookup.friends.names.join(', ') }}</span></p>
        <ul v-if="lookup?.recent_games?.length" class="mt-4 space-y-1 text-sm">
            <li v-for="game in lookup.recent_games" :key="game.appid">
                {{ game.name }} · {{ game.playtime_2weeks }} minutes in two weeks · {{ game.playtime_forever }} minutes total
            </li>
        </ul>
    </section>
</template>
