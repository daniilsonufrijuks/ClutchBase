<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });
defineProps<{ cases: { data: { id: number; name: string; contents_count: number }[] }; seo: SeoMeta }>();
const form = useForm({ name: '', description: '', category: 'case', status: 'unknown', attribute_status: 'unverified', contents: [] as { item_id: number }[] });
</script>

<template>
    <SeoHead :seo="seo" />
    <h1 class="font-display text-3xl">Cases</h1>
    <form class="mt-4 grid max-w-xl gap-2" @submit.prevent="form.post('/admin/cases')">
        <input v-model="form.name" required placeholder="Name" class="rounded border border-line bg-panel px-3 py-2" aria-label="Case name" />
        <button class="rounded bg-brass px-3 py-2 text-ink" type="submit">Create</button>
    </form>
    <ul class="mt-6 text-sm"><li v-for="caseItem in cases.data" :key="caseItem.id">{{ caseItem.name }} · {{ caseItem.contents_count }} contents</li></ul>
</template>
