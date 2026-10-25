<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{ links: { url: string | null; label: string; active: boolean }[] }>();

function label(value: string): string {
    return value.replace('&laquo;', 'Previous').replace('&raquo;', 'Next').replace(/<[^>]+>/g, '');
}
</script>

<template>
    <nav v-if="links.length > 3" aria-label="Pagination" class="flex flex-wrap gap-2">
        <component
            :is="link.url ? Link : 'span'"
            v-for="link in links"
            :key="link.label"
            :href="link.url || undefined"
            class="rounded border px-3 py-1 text-sm"
            :class="link.active ? 'border-brass text-brass-2' : 'border-line text-muted'"
        >
            {{ label(link.label) }}
        </component>
    </nav>
</template>
