<script setup lang="ts">
defineProps<{ slot: number; nickname: string; role: string; selected: boolean }>();
</script>

<template>
    <button type="button" class="absolute -translate-x-1/2 -translate-y-1/2 rounded-full border px-2 py-1 text-xs" :class="selected ? 'border-brass bg-brass text-ink' : 'border-line bg-ink'" :aria-label="`Player ${slot} ${nickname}`">
        {{ slot }} {{ nickname }}
        <span class="block text-[10px] opacity-80">{{ role }}</span>
    </button>
</template>
