<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import { formatTiming } from '@/lib/format';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SeoMeta, StrategyGraph } from '@/types';

defineOptions({ layout: AppLayout });
defineProps<{ strategy: StrategyGraph; seo: SeoMeta }>();
</script>

<template>
    <SeoHead :seo="seo" />
    <button class="no-print rounded border border-line px-3 py-1 text-sm" type="button" @click="window.print()">Print</button>
    <h1 class="mt-4 font-display text-3xl">{{ strategy.title }}</h1>
    <p class="text-sm">{{ strategy.disclaimer }}</p>
    <p class="mt-2 text-sm">{{ strategy.map.name }} · {{ strategy.side.toUpperCase() }} · {{ strategy.objective }}</p>
    <img :src="strategy.map.image" :alt="strategy.map.name" class="mt-4 max-w-xl border" />
    <h2 class="mt-6 font-display text-xl">Players</h2>
    <ul class="mt-2 text-sm">
        <li v-for="player in strategy.players" :key="player.slot">{{ player.slot }}. {{ player.nickname }} · {{ player.role }} · {{ player.responsibilities }}</li>
    </ul>
    <h2 class="mt-6 font-display text-xl">Timeline</h2>
    <ol class="mt-2 text-sm">
        <li v-for="(step, index) in strategy.timeline" :key="index">{{ formatTiming(step.seconds) }} — {{ step.title }}. {{ step.instructions }}</li>
    </ol>
    <h2 class="mt-6 font-display text-xl">Utility</h2>
    <ul class="mt-2 text-sm">
        <li v-for="(action, index) in strategy.utility" :key="index">{{ formatTiming(action.timing_seconds) }} · slot {{ action.player_slot }} · {{ action.utility_type }} · {{ action.notes }}</li>
    </ul>
</template>
