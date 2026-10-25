<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';
import PriceLabel from '@/components/PriceLabel.vue';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Paginator, PriceInfo, SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    cases: Paginator<{ name: string; href: string; image: string; image_alt: string; category: string; attribute_status: string; status: string; price: PriceInfo | null }>;
    filters: Record<string, string | undefined>;
    options: {
        categories: string[];
        collections: { name: string; slug: string }[];
        weapons: { name: string; slug: string }[];
    };
    seo: SeoMeta;
}>();

function apply(event: Event) {
    const data = Object.fromEntries(
        [...new FormData(event.target as HTMLFormElement).entries()].filter(([, value]) => String(value) !== ''),
    );
    router.get('/cases', data, { preserveState: true, preserveScroll: true });
}
</script>

<template>
    <SeoHead :seo="seo" />
    <h1 class="font-display text-3xl">Cases</h1>
    <form class="mt-6 grid gap-3 rounded-xl border border-line bg-panel p-4 md:grid-cols-3" @submit.prevent="apply">
        <input name="q" :value="filters.q" placeholder="Case name" class="rounded border border-line bg-ink px-3 py-2" aria-label="Case name" />
        <select name="category" :value="filters.category || ''" class="rounded border border-line bg-ink px-3 py-2" aria-label="Category">
            <option value="">Any category</option>
            <option v-for="category in options.categories" :key="category" :value="category">{{ category }}</option>
        </select>
        <select name="collection" :value="filters.collection || ''" class="rounded border border-line bg-ink px-3 py-2" aria-label="Collection">
            <option value="">Any collection</option>
            <option v-for="collection in options.collections" :key="collection.slug" :value="collection.slug">{{ collection.name }}</option>
        </select>
        <select name="weapon" :value="filters.weapon || ''" class="rounded border border-line bg-ink px-3 py-2" aria-label="Contains weapon">
            <option value="">Contains any weapon</option>
            <option v-for="weapon in options.weapons" :key="weapon.slug" :value="weapon.slug">{{ weapon.name }}</option>
        </select>
        <select name="key" :value="filters.key || ''" class="rounded border border-line bg-ink px-3 py-2" aria-label="Key">
            <option value="">Key: any</option>
            <option value="yes">Key required</option>
            <option value="no">No key recorded</option>
        </select>
        <select name="sort" :value="filters.sort || 'name'" class="rounded border border-line bg-ink px-3 py-2" aria-label="Sort">
            <option value="name">Name</option>
            <option value="newest">Newest release</option>
            <option value="oldest">Oldest release</option>
        </select>
        <button class="rounded bg-brass px-4 py-2 text-ink" type="submit">Apply</button>
    </form>
    <div v-if="props.cases.data.length" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <Link v-for="caseItem in cases.data" :key="caseItem.href" :href="caseItem.href" class="rounded-xl border border-line bg-panel p-3">
            <img :src="caseItem.image" :alt="caseItem.image_alt" class="aspect-[4/3] w-full rounded object-cover" width="640" height="480" loading="lazy" />
            <h2 class="mt-3 font-display">{{ caseItem.name }}</h2>
            <p class="text-xs uppercase text-muted">{{ caseItem.category }} · {{ caseItem.status }} · {{ caseItem.attribute_status }}</p>
            <PriceLabel :price="caseItem.price" />
        </Link>
    </div>
    <EmptyState v-else class="mt-6" title="No matching cases" body="Clear a filter or search a shorter name." />
    <Pagination class="mt-6" :links="cases.links" />
</template>
