<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ItemCard from '@/components/ItemCard.vue';
import PriceLabel from '@/components/PriceLabel.vue';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { ItemCard as Card, PriceInfo, SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });

defineProps<{
    popular: Card[];
    cases: Array<Card & { href: string; image: string; name: string; attribute_status: string; price: PriceInfo | null }>;
    knives: Card[];
    noteworthy: Card[];
    recent: Card[];
    prices: Array<PriceInfo & { subject: string; subject_id: number }>;
    trending: { term: string; total: number }[];
    guides: { title: string; slug: string; excerpt: string | null }[];
    categories: { label: string; href: string }[];
    seo: SeoMeta;
}>();

const q = ref('');
</script>

<template>
    <SeoHead :seo="seo" />
    <section class="rounded-2xl border border-line bg-panel px-6 py-10">
        <p class="text-sm uppercase tracking-[0.2em] text-brass">Community catalog</p>
        <h1 class="mt-3 max-w-2xl font-display text-4xl leading-tight">Find a finish. Plan the round.</h1>
        <p class="mt-3 max-w-2xl text-muted">ClutchBase is an original CS2 item desk and five-player strategy planner. Sample records stay marked as demo until someone verifies them.</p>
        <form class="mt-6 flex max-w-xl gap-2" @submit.prevent="router.get('/search', { q })">
            <label class="sr-only" for="home-search">Search items</label>
            <input id="home-search" v-model="q" class="w-full rounded border border-line bg-ink px-3 py-2" placeholder="Search skins, cases, collections" />
            <button class="rounded bg-brass px-4 py-2 text-ink" type="submit">Search</button>
        </form>
        <div class="mt-6 flex flex-wrap gap-2">
            <Link v-for="category in categories" :key="category.href" :href="category.href" class="rounded-full border border-line px-3 py-1 text-sm text-muted hover:text-text">{{ category.label }}</Link>
        </div>
    </section>

    <section class="mt-10">
        <h2 class="font-display text-2xl">Popular finishes</h2>
        <div v-if="popular.length" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <ItemCard v-for="item in popular" :key="item.id" :item="item" />
        </div>
        <p v-else class="mt-3 text-sm text-muted">No featured finishes yet.</p>
    </section>

    <section class="mt-10 grid gap-8 lg:grid-cols-2">
        <div>
            <h2 class="font-display text-2xl">Featured cases</h2>
            <div class="mt-4 grid gap-4">
                <Link v-for="caseItem in cases" :key="caseItem.href" :href="caseItem.href" class="flex gap-4 rounded-xl border border-line bg-panel p-3">
                    <img :src="caseItem.image" alt="" class="h-20 w-28 rounded object-cover" width="112" height="80" />
                    <span>
                        <span class="block font-display">{{ caseItem.name }}</span>
                        <span class="text-xs uppercase text-brass-2">{{ caseItem.attribute_status }}</span>
                        <PriceLabel class="mt-1" :price="caseItem.price" />
                    </span>
                </Link>
                <p v-if="!cases.length" class="text-sm text-muted">No featured cases yet.</p>
            </div>
        </div>
        <div>
            <h2 class="font-display text-2xl">Recently updated prices</h2>
            <ul class="mt-4 divide-y divide-line rounded-xl border border-line bg-panel">
                <li v-for="(price, index) in prices" :key="index" class="flex items-center justify-between px-4 py-3 text-sm">
                    <span class="text-muted">{{ price.marketplace || 'Source not recorded' }}</span>
                    <PriceLabel :price="price" />
                </li>
                <li v-if="!prices.length" class="px-4 py-3 text-sm text-muted">No observations stored.</li>
            </ul>
        </div>
    </section>

    <section class="mt-10">
        <h2 class="font-display text-2xl">Knives and noteworthy</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <ItemCard v-for="item in [...knives, ...noteworthy]" :key="item.href" :item="item" />
        </div>
    </section>

    <section class="mt-10">
        <h2 class="font-display text-2xl">Recently updated</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <ItemCard v-for="item in recent" :key="`recent-${item.id}`" :item="item" />
        </div>
    </section>

    <section class="mt-10 grid gap-8 lg:grid-cols-2">
        <div>
            <h2 class="font-display text-2xl">Trending searches</h2>
            <div class="mt-4 flex flex-wrap gap-2">
                <Link v-for="row in trending" :key="row.term" :href="`/search?q=${encodeURIComponent(row.term)}`" class="rounded-full border border-line px-3 py-1 text-sm">{{ row.term }}</Link>
                <p v-if="!trending.length" class="text-sm text-muted">No searches yet.</p>
            </div>
        </div>
        <div>
            <h2 class="font-display text-2xl">Guides</h2>
            <ul class="mt-4 space-y-3">
                <li v-for="guide in guides" :key="guide.slug">
                    <Link :href="`/guides/${guide.slug}`" class="font-display hover:text-brass-2">{{ guide.title }}</Link>
                    <p class="text-sm text-muted">{{ guide.excerpt }}</p>
                </li>
            </ul>
        </div>
    </section>
</template>
