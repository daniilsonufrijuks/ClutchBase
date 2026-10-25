<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import ItemCard from '@/components/ItemCard.vue';
import PriceChart from '@/components/PriceChart.vue';
import PriceLabel from '@/components/PriceLabel.vue';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { ItemCard as Card, PriceInfo, SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    item: Card & {
        description: string | null;
        min_float: string | null;
        max_float: string | null;
        pattern_note: string | null;
        released_on: string | null;
        update_name: string | null;
        verified_at: string | null;
        exteriors: { slug: string; name: string; abbreviation: string; is_verified: boolean }[];
        selected_exterior: string | null;
        selected_variant: string;
        quotes: PriceInfo[];
        history: PriceInfo[];
        history_limited: boolean;
        cases: { name: string; href: string | null; attribute_status: string; probability_known: boolean }[];
        collection_href: string | null;
        weapon_href: string | null;
    };
    related: Card[];
    favorite: boolean;
    breadcrumbs: { label: string; href: string | null }[];
    seo: SeoMeta;
}>();

const favoriteForm = useForm({ type: 'item', slug: props.item.slug });

const variants = ['standard', ...(props.item.stattrak !== false ? ['stattrak'] : []), ...(props.item.souvenir !== false ? ['souvenir'] : [])];

function selectExterior(slug: string) {
    router.get(props.item.href, { exterior: slug, variant: props.item.selected_variant }, { preserveScroll: true });
}
</script>

<template>
    <SeoHead :seo="seo" />
    <Breadcrumbs :items="breadcrumbs" />
    <div class="mt-4 grid gap-8 lg:grid-cols-[1.1fr_0.9fr]">
        <div>
            <img :src="item.image" :alt="item.image_alt" class="w-full rounded-2xl border border-line bg-panel" width="640" height="480" />
            <p class="mt-2 text-xs text-muted">{{ item.image.startsWith('http') ? 'Inventory image from the CS2 game files. It is not a live Steam Market listing.' : 'Placeholder or uploaded artwork.' }}</p>
        </div>
        <div>
            <p class="text-sm uppercase tracking-wide text-muted">{{ item.type }} <span v-if="item.attribute_status !== 'verified'">· {{ item.attribute_status }}</span></p>
            <h1 class="mt-1 font-display text-3xl">{{ item.name }}</h1>
            <p v-if="item.rarity" class="mt-2" :style="{ color: item.rarity.color }">{{ item.rarity.name }}<span v-if="!item.rarity.verified"> (unverified)</span></p>
            <p class="mt-4 text-muted">{{ item.description || 'No description has been stored.' }}</p>
            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-muted">Float range</dt><dd>{{ item.min_float && item.max_float ? `${item.min_float} – ${item.max_float}` : 'Not available' }}</dd></div>
                <div><dt class="text-muted">Pattern</dt><dd>{{ item.pattern_note || 'Not available' }}</dd></div>
                <div><dt class="text-muted">StatTrak</dt><dd>{{ item.stattrak === null ? 'Not verified' : item.stattrak ? 'Listed' : 'Not listed' }}</dd></div>
                <div><dt class="text-muted">Souvenir</dt><dd>{{ item.souvenir === null ? 'Not verified' : item.souvenir ? 'Listed' : 'Not listed' }}</dd></div>
                <div><dt class="text-muted">Released</dt><dd>{{ item.released_on || 'Not available' }}</dd></div>
                <div><dt class="text-muted">Update</dt><dd>{{ item.update_name || 'Not available' }}</dd></div>
            </dl>
            <div class="mt-4 flex flex-wrap gap-2">
                <Link v-if="item.weapon_href" :href="item.weapon_href" class="rounded border border-line px-3 py-1 text-sm">{{ item.weapon }}</Link>
                <Link v-if="item.collection_href" :href="item.collection_href" class="rounded border border-line px-3 py-1 text-sm">{{ item.collection }}</Link>
            </div>
            <button v-if="$page.props.auth.user" type="button" class="mt-4 rounded border border-line px-3 py-1.5 text-sm" @click="favoriteForm.post('/account/favorites')">
                {{ favorite ? 'Remove favorite' : 'Save favorite' }}
            </button>
            <p v-else class="mt-4 text-sm text-muted"><Link href="/login" class="text-brass-2">Log in</Link> to save favorites.</p>
        </div>
    </div>

    <section class="mt-8">
        <h2 class="font-display text-xl">Condition</h2>
        <p v-if="!item.exteriors.length" class="mt-2 text-sm text-muted">No exterior conditions have been verified for this item.</p>
        <div v-else class="mt-3 flex flex-wrap gap-2">
            <button v-for="exterior in item.exteriors" :key="exterior.slug" type="button" class="rounded border px-3 py-1 text-sm" :class="item.selected_exterior === exterior.slug ? 'border-brass' : 'border-line'" @click="selectExterior(exterior.slug)">
                {{ exterior.name }}<span v-if="!exterior.is_verified" class="text-muted"> · unverified</span>
            </button>
        </div>
        <div class="mt-3 flex gap-2 text-sm">
            <button v-for="variant in variants" :key="variant" type="button" class="rounded border px-3 py-1" :class="item.selected_variant === variant ? 'border-brass' : 'border-line'" @click="router.get(item.href, { exterior: item.selected_exterior, variant }, { preserveScroll: true })">{{ variant }}</button>
        </div>
    </section>

    <section class="mt-8 grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-line bg-panel p-4">
            <h2 class="font-display text-xl">Marketplace quotes</h2>
            <p v-if="!item.quotes.length" class="mt-2 text-sm text-muted">No market quotes have been recorded for this selection.</p>
            <table v-else class="mt-3 w-full text-left text-sm">
                <thead class="text-muted"><tr><th>Market</th><th>Quote</th><th>Observed</th></tr></thead>
                <tbody>
                    <tr v-for="(quote, index) in item.quotes" :key="index" class="border-t border-line">
                        <td class="py-2">{{ quote.marketplace || 'Unknown' }}</td>
                        <td><PriceLabel :price="quote" /></td>
                        <td class="text-muted">{{ quote.observed_at || 'Unknown' }}</td>
                    </tr>
                </tbody>
            </table>
            <p v-if="item.history_limited" class="mt-3 text-xs text-muted">Free accounts see 30 days of stored history. Older rows stay in the database.</p>
        </div>
        <PriceChart :points="item.history" />
    </section>

    <section class="mt-8">
        <h2 class="font-display text-xl">Cases</h2>
        <ul class="mt-3 space-y-2 text-sm">
            <li v-for="caseLink in item.cases" :key="caseLink.href || caseLink.name">
                <Link v-if="caseLink.href" :href="caseLink.href">{{ caseLink.name }}</Link>
                <span class="text-muted"> · {{ caseLink.attribute_status }} · drop rate {{ caseLink.probability_known ? 'verified' : 'unknown' }}</span>
            </li>
            <li v-if="!item.cases.length" class="text-muted">No case relationship has been stored.</li>
        </ul>
    </section>

    <section class="mt-8">
        <h2 class="font-display text-xl">Related</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <ItemCard v-for="relatedItem in related" :key="relatedItem.id" :item="relatedItem" />
        </div>
    </section>
</template>
