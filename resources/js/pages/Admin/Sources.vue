<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });
defineProps<{ sources: { name: string; driver: string; last_status: string | null; last_error: string | null; last_synced_at: string | null }[]; provider: string; configured: boolean; seo: SeoMeta }>();
const form = useForm({});
</script>

<template>
    <SeoHead :seo="seo" />
    <h1 class="font-display text-3xl">Price sources</h1>
    <p class="mt-2 text-sm text-muted">Active provider: {{ provider }}. {{ configured ? 'HTTP credentials are set.' : 'No live provider is configured. Demo observations are not presented as market quotes.' }}</p>
    <form class="mt-4" @submit.prevent="form.post('/admin/sources/sync')"><button class="rounded bg-brass px-3 py-2 text-ink" type="submit">Run sync</button></form>
    <ul class="mt-4 space-y-2 text-sm">
        <li v-for="source in sources" :key="source.name" class="rounded border border-line p-3">{{ source.name }} · {{ source.last_status || 'idle' }}<p class="text-muted">{{ source.last_error }}</p></li>
    </ul>
</template>
