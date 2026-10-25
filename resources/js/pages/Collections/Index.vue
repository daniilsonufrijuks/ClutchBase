<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Pagination from '@/components/Pagination.vue';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Paginator, SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });
defineProps<{ collections: Paginator<{ name: string; slug: string; attribute_status: string; items_count: number }>; seo: SeoMeta }>();
</script>

<template>
    <SeoHead :seo="seo" />
    <h1 class="font-display text-3xl">Collections</h1>
    <ul class="mt-6 divide-y divide-line rounded-xl border border-line">
        <li v-for="collection in collections.data" :key="collection.slug" class="flex items-center justify-between px-4 py-3">
            <Link :href="`/collections/${collection.slug}`" class="font-display">{{ collection.name }}</Link>
            <span class="text-sm text-muted">{{ collection.items_count }} items · {{ collection.attribute_status }}</span>
        </li>
    </ul>
    <Pagination class="mt-6" :links="collections.links" />
</template>
