<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PriceLabel from '@/components/PriceLabel.vue';
import type { ItemCard } from '@/types';

defineProps<{ item: ItemCard }>();
</script>

<template>
    <Link :href="item.href" class="group flex flex-col overflow-hidden rounded-xl border border-line bg-panel transition hover:border-brass/70">
        <div class="relative aspect-[4/3] bg-panel-2">
            <img :src="item.image" :alt="item.image_alt" class="h-full w-full object-cover" loading="lazy" width="640" height="480" />
            <span v-if="item.attribute_status !== 'verified'" class="absolute left-2 top-2 rounded bg-ink/80 px-2 py-0.5 text-[11px] uppercase tracking-wide text-brass-2">
                {{ item.attribute_status }}
            </span>
        </div>
        <div class="flex flex-1 flex-col gap-2 p-3">
            <p class="text-xs uppercase tracking-wide text-muted">{{ item.weapon || item.type }}</p>
            <h3 class="font-display text-base leading-snug">{{ item.name }}</h3>
            <p v-if="item.rarity" class="text-sm" :style="{ color: item.rarity.color }">
                {{ item.rarity.name }}
                <span v-if="!item.rarity.verified" class="text-muted"> · unverified</span>
            </p>
            <p v-else class="text-sm text-muted">Rarity not recorded</p>
            <PriceLabel class="mt-auto" :price="item.price" />
        </div>
    </Link>
</template>
