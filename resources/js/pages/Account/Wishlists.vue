<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { ItemCard, SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });
defineProps<{
    wishlists: { id: number; name: string; items: { id: number; note: string | null; item: ItemCard | null }[] }[];
    can_create: boolean;
    seo: SeoMeta;
}>();
const createForm = useForm({ name: '' });
const itemForm = useForm({ slug: '', note: '' });
</script>

<template>
    <SeoHead :seo="seo" />
    <h1 class="font-display text-3xl">Wishlists</h1>
    <p v-if="!can_create" class="mt-2 text-sm text-muted">Another wishlist is a premium entitlement. The public catalog does not require one.</p>
    <form v-else class="mt-4 flex gap-2" @submit.prevent="createForm.post('/account/wishlists')">
        <input v-model="createForm.name" required class="rounded border border-line bg-panel px-3 py-2" aria-label="Wishlist name" />
        <button class="rounded bg-brass px-3 py-2 text-ink" type="submit">Create</button>
    </form>
    <section v-for="list in wishlists" :key="list.id" class="mt-6 rounded-xl border border-line p-4">
        <h2 class="font-display">{{ list.name }}</h2>
        <ul class="mt-2 text-sm">
            <li v-for="row in list.items" :key="row.id" class="flex justify-between gap-3 py-1">
                <Link v-if="row.item" :href="row.item.href">{{ row.item.name }}</Link>
                <Link :href="`/account/wishlists/${list.id}/items/${row.id}`" method="delete" as="button">Remove</Link>
            </li>
        </ul>
        <form class="mt-3 flex gap-2" @submit.prevent="itemForm.post(`/account/wishlists/${list.id}/items`)">
            <input v-model="itemForm.slug" placeholder="Item slug" required class="rounded border border-line bg-ink px-3 py-2" aria-label="Item slug" />
            <button class="rounded border border-line px-3" type="submit">Add</button>
        </form>
    </section>
</template>
