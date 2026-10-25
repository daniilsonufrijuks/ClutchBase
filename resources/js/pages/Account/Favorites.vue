<script setup lang="ts">
import ItemCard from '@/components/ItemCard.vue';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { ItemCard as Card, SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });
defineProps<{ items: Card[]; cases: { name: string; href: string }[]; seo: SeoMeta }>();
</script>

<template>
    <SeoHead :seo="seo" />
    <h1 class="font-display text-3xl">Favorites</h1>
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <ItemCard v-for="item in items" :key="item.id" :item="item" />
    </div>
    <ul class="mt-4 text-sm">
        <li v-for="caseItem in cases" :key="caseItem.href"><a :href="caseItem.href">{{ caseItem.name }}</a></li>
    </ul>
    <p v-if="!items.length && !cases.length" class="mt-4 text-sm text-muted">No favorites yet.</p>
</template>
