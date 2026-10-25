<script setup lang="ts">
import { computed } from 'vue';
import type { PriceInfo } from '@/types';

const props = defineProps<{ points: PriceInfo[] }>();
const plotted = computed(() => props.points.filter((point) => point.amount !== null));
const demo = computed(() => plotted.value.some((point) => point.is_demo));

const geometry = computed(() => {
    if (plotted.value.length < 2) {
        return null;
    }
    const values = plotted.value.map((point) => Number(point.amount));
    const min = Math.min(...values);
    const max = Math.max(...values);
    const span = max - min || 1;
    return plotted.value
        .map((point, index) => {
            const x = (index / (plotted.value.length - 1)) * 100;
            const y = 100 - ((Number(point.amount) - min) / span) * 100;
            return `${x},${y}`;
        })
        .join(' ');
});
</script>

<template>
    <div class="rounded-xl border border-line bg-panel p-4">
        <h3 class="font-display text-sm">Price history</h3>
        <p v-if="demo" class="mt-1 text-xs text-brass-2">This chart includes demo observations and is not a market record.</p>
        <p v-if="!geometry" class="mt-3 text-sm text-muted">Not enough historical observations to chart.</p>
        <svg v-else viewBox="0 0 100 100" class="mt-3 h-36 w-full" role="img" aria-label="Price history chart">
            <polyline :points="geometry" fill="none" stroke="#d7a35a" stroke-width="2" vector-effect="non-scaling-stroke" />
        </svg>
    </div>
</template>
