<script setup lang="ts">
import { priceLabel } from '@/lib/format';
import type { PriceInfo } from '@/types';

defineProps<{ price: PriceInfo | null }>();
</script>

<template>
    <span class="inline-flex flex-wrap items-center gap-1 text-sm">
        <span>{{ priceLabel(price) }}</span>
        <span v-if="price?.is_demo" class="rounded bg-brass/20 px-1.5 py-0.5 text-[11px] text-brass-2">Demo</span>
        <span v-else-if="price && !price.is_verified" class="rounded bg-white/10 px-1.5 py-0.5 text-[11px] text-muted">Unverified</span>
        <span v-if="price?.stale" class="text-[11px] text-muted">Stale</span>
    </span>
</template>
