<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });
defineProps<{ guide: { title: string; body: string }; breadcrumbs: { label: string; href: string | null }[]; seo: SeoMeta }>();
</script>

<template>
    <SeoHead :seo="seo" />
    <Breadcrumbs :items="breadcrumbs" />
    <article class="mt-4 max-w-2xl">
        <h1 class="font-display text-3xl">{{ guide.title }}</h1>
        <p v-for="(paragraph, index) in guide.body.split('\n\n')" :key="index" class="mt-4 text-muted">{{ paragraph }}</p>
    </article>
</template>
