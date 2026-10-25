<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{ items: { label: string; href: string | null }[] }>();
</script>

<template>
    <nav aria-label="Breadcrumb" class="text-sm text-muted">
        <ol class="flex flex-wrap items-center gap-2">
            <li v-for="(item, index) in items" :key="`${item.label}-${index}`" class="flex items-center gap-2">
                <Link v-if="item.href" :href="item.href" class="hover:text-text">{{ item.label }}</Link>
                <span v-else class="text-text">{{ item.label }}</span>
                <span v-if="index < items.length - 1" aria-hidden="true">/</span>
            </li>
        </ol>
    </nav>
</template>
