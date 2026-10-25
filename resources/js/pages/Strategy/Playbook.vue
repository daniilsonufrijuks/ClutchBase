<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import TeamPlaybookPage from '@/components/strategy/TeamPlaybookPage.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });
defineProps<{
    strategies: { data: { id: number; title: string; side: string; objective: string; status: string; updated_at: string; map?: { name: string } }[]; links: { url: string | null; label: string; active: boolean }[] };
    teams: { id: number; name: string; role: string }[];
    teamId: number;
    maps: { slug: string; name: string }[];
    filters: Record<string, string | undefined>;
    objectives: Record<string, string>;
    seo: SeoMeta;
}>();
</script>

<template>
    <SeoHead :seo="seo" />
    <TeamPlaybookPage :strategies="strategies" :teams="teams" :team-id="teamId" :maps="maps" :filters="filters" :objectives="objectives" />
</template>
