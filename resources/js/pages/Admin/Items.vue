<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });
defineProps<{ items: { data: { id: number; name: string; status: string; attribute_status: string }[] }; weapons: { id: number; name: string }[]; rarities: { id: number; name: string }[]; collections: { id: number; name: string }[]; exteriors: { id: number; name: string }[]; seo: SeoMeta }>();
const form = useForm({
    type: 'skin', name: '', weapon_id: '', rarity_id: '', collection_id: '', description: '', status: 'published', attribute_status: 'unverified', exterior_ids: [] as number[],
});
</script>

<template>
    <SeoHead :seo="seo" />
    <h1 class="font-display text-3xl">Items</h1>
    <form class="mt-4 grid max-w-xl gap-2" @submit.prevent="form.post('/admin/items')">
        <input v-model="form.name" required placeholder="Name" class="rounded border border-line bg-panel px-3 py-2" aria-label="Name" />
        <select v-model="form.type" class="rounded border border-line bg-panel px-3 py-2" aria-label="Type"><option>skin</option><option>knife</option><option>glove</option><option>sticker</option><option>agent</option></select>
        <select v-model="form.weapon_id" class="rounded border border-line bg-panel px-3 py-2" aria-label="Weapon"><option value="">No weapon</option><option v-for="weapon in weapons" :key="weapon.id" :value="weapon.id">{{ weapon.name }}</option></select>
        <select v-model="form.rarity_id" class="rounded border border-line bg-panel px-3 py-2" aria-label="Rarity"><option value="">No rarity</option><option v-for="rarity in rarities" :key="rarity.id" :value="rarity.id">{{ rarity.name }}</option></select>
        <select v-model="form.status" class="rounded border border-line bg-panel px-3 py-2" aria-label="Status"><option>draft</option><option>published</option><option>archived</option></select>
        <select v-model="form.attribute_status" class="rounded border border-line bg-panel px-3 py-2" aria-label="Attribute status"><option>demo</option><option>unverified</option><option>verified</option></select>
        <button class="rounded bg-brass px-3 py-2 text-ink" type="submit">Create</button>
    </form>
    <ul class="mt-6 text-sm">
        <li v-for="item in items.data" :key="item.id" class="flex justify-between border-b border-line py-2">
            <span>{{ item.name }} · {{ item.status }} · {{ item.attribute_status }}</span>
            <button type="button" class="text-muted" @click="useForm({}).delete(`/admin/items/${item.id}`)">Delete</button>
        </li>
    </ul>
</template>
