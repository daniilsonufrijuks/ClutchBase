<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import ItemCard from '@/components/ItemCard.vue';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { ItemCard as Card, SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });
defineProps<{ weapon: { name: string; category: string }; items: Card[]; breadcrumbs: { label: string; href: string | null }[]; seo: SeoMeta }>();
</script>

<template>
    <SeoHead :seo="seo" />
    <Breadcrumbs :items="breadcrumbs" />
    <h1 class="mt-3 font-display text-3xl">{{ weapon.name }}</h1>
    <p class="text-sm text-muted">{{ weapon.category }}</p>
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <ItemCard v-for="item in items" :key="item.id" :item="item" />
    </div>
</template>
