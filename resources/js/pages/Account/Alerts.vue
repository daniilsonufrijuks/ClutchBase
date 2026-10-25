<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });
defineProps<{ alerts: { id: number; threshold: string; direction: string; item?: { name: string } }[]; checkout_configured: boolean; seo: SeoMeta }>();
const form = useForm({ slug: '', threshold: '', direction: 'below', variant: 'standard' });
</script>

<template>
    <SeoHead :seo="seo" />
    <h1 class="font-display text-3xl">Price alerts</h1>
    <p class="mt-2 max-w-2xl text-sm text-muted">Alerts are a premium entitlement checked on the server. No payment provider is configured in this install, so an administrator grants premium manually.</p>
    <form v-if="$page.props.auth.user?.premium" class="mt-4 grid max-w-lg gap-2" @submit.prevent="form.post('/account/alerts')">
        <input v-model="form.slug" required placeholder="Item slug" class="rounded border border-line bg-panel px-3 py-2" aria-label="Item slug" />
        <input v-model="form.threshold" required placeholder="Threshold" class="rounded border border-line bg-panel px-3 py-2" aria-label="Threshold" />
        <select v-model="form.direction" class="rounded border border-line bg-panel px-3 py-2" aria-label="Direction"><option value="below">Below</option><option value="above">Above</option></select>
        <button class="rounded bg-brass px-3 py-2 text-ink" type="submit">Save alert</button>
    </form>
    <ul class="mt-4 text-sm">
        <li v-for="alert in alerts" :key="alert.id">{{ alert.item?.name }} {{ alert.direction }} {{ alert.threshold }}</li>
    </ul>
</template>
