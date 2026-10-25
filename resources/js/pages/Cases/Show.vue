<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import PriceChart from '@/components/PriceChart.vue';
import PriceLabel from '@/components/PriceLabel.vue';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { ItemCard, PriceInfo, SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    caseItem: {
        name: string; slug: string; href: string; image: string; image_alt: string; description: string | null;
        status: string; category: string; attribute_status: string; requires_key: boolean | null;
        price: PriceInfo | null; key_price: PriceInfo | null; released_on: string | null;
        collection: { name: string; href: string; attribute_status: string } | null;
        groups: { name: string; color: string; items: Array<ItemCard & { is_special: boolean; probability_known: boolean }> }[];
        history: PriceInfo[]; quotes: PriceInfo[];
    };
    related: { name: string; href: string }[];
    favorite: boolean;
    breadcrumbs: { label: string; href: string | null }[];
    seo: SeoMeta;
}>();

const filter = ref('');
const weapon = ref('');
const specialOnly = ref(false);
const favoriteForm = useForm({ type: 'case', slug: props.caseItem.slug });
const weapons = computed(() => [...new Set(props.caseItem.groups.flatMap((group) => group.items.map((item) => item.weapon).filter((name): name is string => Boolean(name))))].sort());
const groups = computed(() => props.caseItem.groups.map((group) => ({
    ...group,
    items: group.items.filter((item) => {
        const name = item.name.toLowerCase().includes(filter.value.toLowerCase());
        const weaponMatch = !weapon.value || item.weapon === weapon.value;
        const specialMatch = !specialOnly.value || item.is_special;

        return name && weaponMatch && specialMatch;
    }),
})).filter((group) => group.items.length));
</script>

<template>
    <SeoHead :seo="seo" />
    <Breadcrumbs :items="breadcrumbs" />
    <div class="mt-4 grid gap-6 lg:grid-cols-[280px_1fr]">
        <img :src="caseItem.image" :alt="caseItem.image_alt" class="rounded-xl border border-line" width="640" height="480" />
        <div>
            <p class="text-xs uppercase text-muted">{{ caseItem.category }} · {{ caseItem.attribute_status }}</p>
            <h1 class="font-display text-3xl">{{ caseItem.name }}</h1>
            <p class="mt-2 text-muted">{{ caseItem.description }}</p>
            <dl class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                <div><dt class="text-muted">Status</dt><dd>{{ caseItem.status === 'unknown' ? 'Not verified' : caseItem.status }}</dd></div>
                <div><dt class="text-muted">Released</dt><dd>{{ caseItem.released_on || 'Not available' }}</dd></div>
                <div><dt class="text-muted">Case price</dt><dd><PriceLabel :price="caseItem.price" /></dd></div>
                <div><dt class="text-muted">Key price</dt><dd>{{ caseItem.key_price ? '' : 'Not available' }}<PriceLabel v-if="caseItem.key_price" :price="caseItem.key_price" /></dd></div>
                <div><dt class="text-muted">Key required</dt><dd>{{ caseItem.requires_key === null ? 'Not verified' : caseItem.requires_key ? 'Yes' : 'No' }}</dd></div>
            </dl>
            <div class="mt-4 flex gap-3 text-sm">
                <Link v-if="caseItem.collection" :href="caseItem.collection.href">{{ caseItem.collection.name }}</Link>
                <button v-if="$page.props.auth.user" type="button" @click="favoriteForm.post('/account/favorites')">{{ favorite ? 'Remove bookmark' : 'Bookmark case' }}</button>
            </div>
        </div>
    </div>
    <div class="mt-8 flex flex-wrap gap-3">
        <label class="text-sm text-muted" for="content-filter">Filter contents
            <input id="content-filter" v-model="filter" class="mt-1 block w-64 rounded border border-line bg-panel px-3 py-2 text-text" placeholder="Item name" />
        </label>
        <label class="text-sm text-muted" for="weapon-filter">Weapon
            <select id="weapon-filter" v-model="weapon" class="mt-1 block rounded border border-line bg-panel px-3 py-2 text-text">
                <option value="">Any weapon</option>
                <option v-for="name in weapons" :key="name" :value="name">{{ name }}</option>
            </select>
        </label>
        <label class="mt-6 flex items-center gap-2 text-sm"><input v-model="specialOnly" type="checkbox" /> Special rare only</label>
    </div>
    <section v-for="group in groups" :key="group.name" class="mt-6">
        <h2 class="font-display text-xl" :style="{ color: group.color }">{{ group.name }}</h2>
        <ul class="mt-2 divide-y divide-line rounded-xl border border-line">
            <li v-for="item in group.items" :key="item.id" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm">
                <Link :href="item.href">{{ item.name }}</Link>
                <span class="text-muted">{{ item.is_special ? 'Special rare · ' : '' }}drop rate {{ item.probability_known ? 'verified' : 'unknown' }}</span>
                <PriceLabel :price="item.price" />
            </li>
        </ul>
    </section>
    <div class="mt-8 grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-line p-4">
            <h2 class="font-display">Case quotes</h2>
            <p v-if="!caseItem.quotes.length" class="mt-2 text-sm text-muted">No case price has been recorded. Content prices above are separate.</p>
            <p v-for="(quote, index) in caseItem.quotes" :key="index" class="mt-2 text-sm"><PriceLabel :price="quote" /></p>
        </div>
        <PriceChart :points="caseItem.history" />
    </div>
    <h2 class="mt-8 font-display text-xl">Related cases</h2>
    <div class="mt-2 flex flex-wrap gap-3 text-sm">
        <Link v-for="item in related" :key="item.href" :href="item.href">{{ item.name }}</Link>
    </div>
</template>
