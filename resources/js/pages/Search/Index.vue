<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import ItemCard from '@/components/ItemCard.vue';
import Pagination from '@/components/Pagination.vue';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { ItemCard as Card, Paginator, SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });
defineProps<{
    items: Paginator<Card>;
    suggestions: { name: string; href: string; meta: string }[];
    recent: string[];
    filters: { q?: string };
    seo: SeoMeta;
}>();
</script>

<template>
    <SeoHead :seo="seo" />
    <h1 class="font-display text-3xl">Search</h1>
    <p v-if="recent.length" class="mt-3 text-sm text-muted">Recent: <Link v-for="term in recent" :key="term" class="mr-2 underline" :href="`/search?q=${encodeURIComponent(term)}`">{{ term }}</Link></p>
    <ul class="mt-4 space-y-1 text-sm">
        <li v-for="suggestion in suggestions" :key="suggestion.href"><Link :href="suggestion.href">{{ suggestion.name }}</Link> <span class="text-muted">{{ suggestion.meta }}</span></li>
    </ul>
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <ItemCard v-for="item in items.data" :key="item.id" :item="item" />
    </div>
    <p v-if="!items.data.length" class="mt-6 text-sm text-muted">No published records matched.</p>
    <Pagination class="mt-6" :links="items.links" />
</template>
