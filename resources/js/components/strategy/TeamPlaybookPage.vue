<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';

defineProps<{
    strategies: { data: { id: number; title: string; side: string; objective: string; status: string; updated_at: string; map?: { name: string } }[]; links: { url: string | null; label: string; active: boolean }[] };
    teams: { id: number; name: string; role: string }[];
    teamId: number;
    maps: { slug: string; name: string }[];
    filters: Record<string, string | undefined>;
    objectives: Record<string, string>;
}>();

function filter(event: Event) {
    router.get('/strategies', Object.fromEntries(new FormData(event.target as HTMLFormElement).entries()), { preserveState: true });
}
</script>

<template>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-3xl">Playbook</h1>
            <p class="text-sm text-muted">Private plans stay out of search indexes.</p>
        </div>
        <Link href="/strategies/create" class="rounded bg-brass px-3 py-2 text-sm text-ink">New strategy</Link>
    </div>
    <form class="mt-4 grid gap-2 md:grid-cols-5" @change="filter" @submit.prevent>
        <select name="team" class="rounded border border-line bg-panel px-2 py-2" aria-label="Team">
            <option v-for="team in teams" :key="team.id" :value="team.id" :selected="team.id === teamId">{{ team.name }} · {{ team.role }}</option>
        </select>
        <select name="map" class="rounded border border-line bg-panel px-2 py-2" aria-label="Map">
            <option value="">Any map</option>
            <option v-for="map in maps" :key="map.slug" :value="map.slug">{{ map.name }}</option>
        </select>
        <select name="side" class="rounded border border-line bg-panel px-2 py-2" aria-label="Side"><option value="">Any side</option><option value="t">T</option><option value="ct">CT</option></select>
        <select name="objective" class="rounded border border-line bg-panel px-2 py-2" aria-label="Objective"><option value="">Any objective</option><option v-for="(label, key) in objectives" :key="key" :value="key">{{ label }}</option></select>
        <select name="status" class="rounded border border-line bg-panel px-2 py-2" aria-label="Status"><option value="">Any status</option><option value="active">Active</option><option value="archived">Archived</option></select>
    </form>
    <ul class="mt-6 divide-y divide-line rounded-xl border border-line">
        <li v-for="strategy in strategies.data" :key="strategy.id" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
            <Link :href="`/strategies/${strategy.id}`" class="font-display">{{ strategy.title }}</Link>
            <span class="text-sm text-muted">{{ strategy.map?.name }} · {{ strategy.side.toUpperCase() }} · {{ strategy.status }}</span>
        </li>
        <li v-if="!strategies.data.length" class="px-4 py-6 text-sm text-muted">No strategies in this playbook yet.</li>
    </ul>
</template>
