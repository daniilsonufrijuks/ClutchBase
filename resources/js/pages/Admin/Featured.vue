<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });
defineProps<{ placements: { id: number; slot: string; subject_type: string; subject_id: number }[]; items: { id: number; name: string }[]; cases: { id: number; name: string }[]; seo: SeoMeta }>();
const form = useForm({ slot: 'popular', subject_type: 'item', subject_id: '', position: 0 });
</script>

<template>
    <SeoHead :seo="seo" />
    <h1 class="font-display text-3xl">Featured</h1>
    <form class="mt-4 flex flex-wrap gap-2" @submit.prevent="form.post('/admin/featured')">
        <select v-model="form.slot" class="rounded border border-line bg-panel px-2 py-2" aria-label="Slot"><option>popular</option><option>case</option><option>knife</option><option>noteworthy</option></select>
        <select v-model="form.subject_type" class="rounded border border-line bg-panel px-2 py-2" aria-label="Subject type"><option value="item">item</option><option value="case">case</option></select>
        <select v-model="form.subject_id" class="rounded border border-line bg-panel px-2 py-2" aria-label="Subject">
            <option value="">Choose</option>
            <option v-for="item in (form.subject_type === 'case' ? cases : items)" :key="item.id" :value="item.id">{{ item.name }}</option>
        </select>
        <button class="rounded bg-brass px-3 py-2 text-ink" type="submit">Add</button>
    </form>
    <ul class="mt-4 text-sm text-muted"><li v-for="placement in placements" :key="placement.id">{{ placement.slot }} · {{ placement.subject_type }} {{ placement.subject_id }}</li></ul>
</template>
