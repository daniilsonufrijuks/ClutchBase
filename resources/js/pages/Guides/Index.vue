<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });
defineProps<{ guides: { title: string; slug: string; excerpt: string | null }[]; seo: SeoMeta }>();
</script>

<template>
    <SeoHead :seo="seo" />
    <h1 class="font-display text-3xl">Guides</h1>
    <ul class="mt-6 space-y-4">
        <li v-for="guide in guides" :key="guide.slug">
            <Link :href="`/guides/${guide.slug}`" class="font-display text-xl">{{ guide.title }}</Link>
            <p class="text-sm text-muted">{{ guide.excerpt }}</p>
        </li>
    </ul>
</template>
