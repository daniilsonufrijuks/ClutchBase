<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });
defineProps<{ jobs: { id: number; original_name: string; status: string; created_count: number; updated_count: number; skipped_count: number; errors: { row: number; message: string }[] | null }[]; seo: SeoMeta }>();
const form = useForm<{ file: File | null }>({ file: null });
function submit() {
    form.post('/admin/imports', { forceFormData: true });
}
</script>

<template>
    <SeoHead :seo="seo" />
    <h1 class="font-display text-3xl">CSV import</h1>
    <p class="mt-2 max-w-2xl text-sm text-muted">Columns: record_type, name, slug, type, weapon_slug, rarity_slug, collection_slug, description, case_slug, item_slug, drop_group, is_special, min_float, max_float, has_stattrak, has_souvenir. Empty floats stay empty. A repeated slug updates the existing row. A duplicate slug inside one file is skipped.</p>
    <form class="mt-4" @submit.prevent="submit">
        <input type="file" accept=".csv,text/csv" required aria-label="CSV file" @change="form.file = ($event.target as HTMLInputElement).files?.[0] || null" />
        <button class="ml-2 rounded bg-brass px-3 py-2 text-ink" type="submit">Import</button>
    </form>
    <article v-for="job in jobs" :key="job.id" class="mt-4 rounded border border-line p-3 text-sm">
        <p>{{ job.original_name }} · {{ job.status }} · created {{ job.created_count }} · updated {{ job.updated_count }} · skipped {{ job.skipped_count }}</p>
        <ul class="text-muted"><li v-for="error in job.errors || []" :key="`${job.id}-${error.row}`">Row {{ error.row }}: {{ error.message }}</li></ul>
    </article>
</template>
