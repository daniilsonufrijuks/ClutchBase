<script setup lang="ts">
defineProps<{ kind: string; label: string | null }>();
const colors: Record<string, string> = { smoke: '#8eb6d9', flash: '#f0d7a8', he: '#d7a35a', molotov: '#eb6a4b', label: '#edf0f5', step: '#b7c0ce' };
</script>

<template>
    <button type="button" class="absolute -translate-x-1/2 -translate-y-1/2 rounded px-1.5 py-0.5 text-[11px]" :style="{ background: colors[kind] || '#edf0f5', color: '#101318' }">
        {{ kind }}<span v-if="label"> · {{ label }}</span>
    </button>
</template>
