<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import EmptyState from '@/components/EmptyState.vue';
import ItemCard from '@/components/ItemCard.vue';
import Pagination from '@/components/Pagination.vue';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { ItemCard as Card, Paginator, SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    items: Paginator<Card>;
    filters: Record<string, string | undefined>;
    options: {
        weapons: { name: string; slug: string }[];
        rarities: { name: string; slug: string }[];
        collections: { name: string; slug: string }[];
        exteriors: { name: string; slug: string }[];
    };
    seo: SeoMeta;
}>();

function apply(event: Event) {
    const data = Object.fromEntries(new FormData(event.target as HTMLFormElement).entries());
    router.get(window.location.pathname, data, { preserveState: true, preserveScroll: true });
}
</script>

<template>
    <SeoHead :seo="seo" />
    <h1 class="font-display text-3xl">Catalog</h1>
    <p class="mt-2 max-w-2xl text-sm text-muted">Price filters use verified, non-demo quotes only. Demo prices stay visible on cards and are excluded from that sort.</p>
    <form class="mt-6 grid gap-3 rounded-xl border border-line bg-panel p-4 md:grid-cols-4" @submit.prevent="apply">
        <input name="q" :value="filters.q" placeholder="Name" class="rounded border border-line bg-ink px-3 py-2" aria-label="Name" />
        <select name="weapon" class="rounded border border-line bg-ink px-3 py-2" aria-label="Weapon">
            <option value="">Any weapon</option>
            <option v-for="weapon in options.weapons" :key="weapon.slug" :value="weapon.slug" :selected="filters.weapon === weapon.slug">{{ weapon.name }}</option>
        </select>
        <select name="collection" class="rounded border border-line bg-ink px-3 py-2" aria-label="Collection">
            <option value="">Any collection</option>
            <option v-for="collection in options.collections" :key="collection.slug" :value="collection.slug" :selected="filters.collection === collection.slug">{{ collection.name }}</option>
        </select>
        <select name="rarity" class="rounded border border-line bg-ink px-3 py-2" aria-label="Rarity">
            <option value="">Any rarity</option>
            <option v-for="rarity in options.rarities" :key="rarity.slug" :value="rarity.slug" :selected="filters.rarity === rarity.slug">{{ rarity.name }}</option>
        </select>
        <select name="exterior" class="rounded border border-line bg-ink px-3 py-2" aria-label="Exterior">
            <option value="">Any recorded exterior</option>
            <option v-for="exterior in options.exteriors" :key="exterior.slug" :value="exterior.slug" :selected="filters.exterior === exterior.slug">{{ exterior.name }}</option>
        </select>
        <input name="min_price" :value="filters.min_price" placeholder="Min verified price" class="rounded border border-line bg-ink px-3 py-2" aria-label="Minimum verified price" />
        <input name="max_price" :value="filters.max_price" placeholder="Max verified price" class="rounded border border-line bg-ink px-3 py-2" aria-label="Maximum verified price" />
        <select name="sort" class="rounded border border-line bg-ink px-3 py-2" aria-label="Sort">
            <option value="name" :selected="filters.sort === 'name'">Name</option>
            <option value="rarity" :selected="filters.sort === 'rarity'">Rarity</option>
            <option value="price" :selected="filters.sort === 'price'">Verified price</option>
        </select>
        <select name="stattrak" class="rounded border border-line bg-ink px-3 py-2" aria-label="StatTrak">
            <option value="">StatTrak: any</option>
            <option value="yes">Verified StatTrak</option>
            <option value="no">Verified without StatTrak</option>
        </select>
        <select name="souvenir" class="rounded border border-line bg-ink px-3 py-2" aria-label="Souvenir">
            <option value="">Souvenir: any</option>
            <option value="yes">Verified Souvenir</option>
            <option value="no">Verified without Souvenir</option>
        </select>
        <input type="hidden" name="type" :value="filters.type || ''" />
        <button class="rounded bg-brass px-4 py-2 text-ink" type="submit">Apply</button>
    </form>
    <div v-if="props.items.data.length" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <ItemCard v-for="item in props.items.data" :key="item.id" :item="item" />
    </div>
    <EmptyState v-else class="mt-6" title="No matching records" body="Try a shorter name or clear the price filter. Verified prices are required before a price range can match." />
    <Pagination class="mt-6" :links="items.links" />
</template>
