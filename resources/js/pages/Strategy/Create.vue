<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import SeoHead from '@/components/SeoHead.vue';
import StrategyTemplateSelector from '@/components/strategy/StrategyTemplateSelector.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });
const props = defineProps<{
    maps: { id: number; name: string }[];
    teams: { id: number; name: string }[];
    templates: { id: number; title: string; side: string; objective: string; map: string | null; summary: string | null }[];
    objectives: Record<string, string>;
    disclaimer: string;
    seo: SeoMeta;
}>();
const templateId = ref<number | null>(null);
const form = useForm({
    title: 'New execute',
    map_asset_id: props.maps[0]?.id ?? '',
    team_id: props.teams[0]?.id ?? '',
    side: 't',
    objective: 'a_execute',
    template_id: null as number | null,
    summary: props.disclaimer,
});
watch(templateId, (id) => {
    const template = props.templates.find((item) => item.id === id);
    if (!template) {
        return;
    }
    form.side = template.side;
    form.objective = template.objective;
    const map = props.maps.find((item) => item.name === template.map);
    if (map) {
        form.map_asset_id = map.id;
    }
});

function submit() {
    form.template_id = templateId.value;
    form.post('/strategies');
}
</script>

<template>
    <SeoHead :seo="seo" />
    <h1 class="font-display text-3xl">New strategy</h1>
    <p class="mt-2 max-w-2xl text-sm text-muted">{{ disclaimer }}</p>
    <form class="mt-6 grid max-w-xl gap-3" @submit.prevent="submit">
        <label>Title <input v-model="form.title" required class="mt-1 w-full rounded border border-line bg-panel px-3 py-2" /></label>
        <label>Map <select v-model="form.map_asset_id" class="mt-1 w-full rounded border border-line bg-panel px-3 py-2"><option v-for="map in maps" :key="map.id" :value="map.id">{{ map.name }}</option></select></label>
        <label>Team <select v-model="form.team_id" class="mt-1 w-full rounded border border-line bg-panel px-3 py-2"><option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option></select></label>
        <label>Side <select v-model="form.side" class="mt-1 w-full rounded border border-line bg-panel px-3 py-2"><option value="t">Terrorist</option><option value="ct">Counter-Terrorist</option></select></label>
        <label>Objective <select v-model="form.objective" class="mt-1 w-full rounded border border-line bg-panel px-3 py-2"><option v-for="(label, key) in objectives" :key="key" :value="key">{{ label }}</option></select></label>
        <StrategyTemplateSelector v-model="templateId" :templates="templates" />
        <button class="rounded bg-brass px-4 py-2 text-ink" type="submit">Create plan</button>
    </form>
</template>
