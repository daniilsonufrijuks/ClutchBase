<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import ItemCard from '@/components/ItemCard.vue';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { ItemCard as Card, SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });
const props = defineProps<{
    collection: { name: string; slug: string; description: string | null; released_on: string | null; attribute_status: string };
    items: Card[];
    filters: { weapon?: string; rarity?: string };
    breadcrumbs: { label: string; href: string | null }[];
    seo: SeoMeta;
}>();

function apply(event: Event) {
    router.get(`/collections/${props.collection.slug}`, Object.fromEntries(new FormData(event.target as HTMLFormElement).entries()));
}
</script>

<template>
    <SeoHead :seo="seo" />
    <Breadcrumbs :items="breadcrumbs" />
    <h1 class="mt-3 font-display text-3xl">{{ collection.name }}</h1>
    <p class="mt-2 text-sm text-muted">{{ collection.attribute_status }} · released {{ collection.released_on || 'not available' }}</p>
    <p class="mt-3 max-w-2xl">{{ collection.description }}</p>
    <form class="mt-4 flex flex-wrap gap-2" @submit.prevent="apply">
        <input name="weapon" :value="filters.weapon" placeholder="Weapon slug" class="rounded border border-line bg-panel px-3 py-2" aria-label="Weapon slug" />
        <input name="rarity" :value="filters.rarity" placeholder="Rarity slug" class="rounded border border-line bg-panel px-3 py-2" aria-label="Rarity slug" />
        <button class="rounded bg-brass px-3 py-2 text-ink" type="submit">Filter</button>
    </form>
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <ItemCard v-for="item in items" :key="item.id" :item="item" />
    </div>
</template>
