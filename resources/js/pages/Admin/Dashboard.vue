<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });
defineProps<{ stats: Record<string, number>; searches: { term: string; total: number }[]; sources: { name: string; last_status: string | null }[]; seo: SeoMeta }>();
</script>

<template>
    <SeoHead :seo="seo" />
    <h1 class="font-display text-3xl">Admin</h1>
    <nav class="mt-4 flex flex-wrap gap-3 text-sm">
        <Link href="/admin/items">Items</Link>
        <Link href="/admin/cases">Cases</Link>
        <Link href="/admin/imports">Imports</Link>
        <Link href="/admin/users">Users</Link>
        <Link href="/admin/sources">Price sources</Link>
        <Link href="/admin/featured">Featured</Link>
    </nav>
    <dl class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div v-for="(value, key) in stats" :key="key" class="rounded border border-line p-3"><dt class="text-xs uppercase text-muted">{{ key }}</dt><dd class="font-display text-2xl">{{ value }}</dd></div>
    </dl>
    <h2 class="mt-6 font-display">Searches</h2>
    <ul class="text-sm text-muted"><li v-for="row in searches" :key="row.term">{{ row.term }} · {{ row.total }}</li></ul>
    <h2 class="mt-6 font-display">Sources</h2>
    <ul class="text-sm text-muted"><li v-for="source in sources" :key="source.name">{{ source.name }} · {{ source.last_status || 'idle' }}</li></ul>
</template>
