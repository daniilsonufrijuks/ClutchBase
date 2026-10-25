<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import StrategyPlannerPage from '@/components/strategy/StrategyPlannerPage.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SeoMeta, StrategyGraph } from '@/types';

defineOptions({ layout: AppLayout });
defineProps<{
    strategy: StrategyGraph;
    can_edit: boolean;
    roles: Record<'t' | 'ct', Record<string, string>>;
    objectives: Record<string, string>;
    utility: Record<string, string>;
    maps: { id: number; name: string }[];
    folders: { id: number; name: string }[];
    guides: { title: string; slug: string }[];
    seo: SeoMeta;
}>();
</script>

<template>
    <SeoHead :seo="seo" />
    <StrategyPlannerPage :strategy="strategy" :can-edit="can_edit" :roles="roles" :objectives="objectives" :utility-names="utility" :maps="maps" :folders="folders" :guides="guides" />
</template>
