<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import type { SeoMeta } from '@/types';

defineProps<{ seo: SeoMeta }>();
</script>

<template>
    <Head>
        <title>{{ seo.title }}</title>
        <meta name="description" :content="seo.description" head-key="description" />
        <link rel="canonical" :href="seo.canonical" head-key="canonical" />
        <meta name="robots" :content="seo.robots" head-key="robots" />
        <meta property="og:title" :content="seo.title" />
        <meta property="og:description" :content="seo.description" />
        <meta property="og:url" :content="seo.canonical" />
        <meta v-if="seo.image" property="og:image" :content="seo.image" />
        <meta property="og:type" content="website" />
    </Head>
    <component
        :is="'script'"
        v-for="(block, index) in seo.jsonLd || []"
        :key="index"
        type="application/ld+json"
    >{{ JSON.stringify(block) }}</component>
</template>
