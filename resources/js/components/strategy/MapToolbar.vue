<script setup lang="ts">
defineProps<{ tool: string; scale: number }>();
const emit = defineEmits<{ select: [tool: string]; zoom: [direction: number] }>();
const tools = [
    ['pan', 'Pan'],
    ['player', 'Player'],
    ['smoke', 'Smoke'],
    ['flash', 'Flash'],
    ['he', 'HE'],
    ['molotov', 'Molotov'],
    ['label', 'Label'],
    ['path', 'Path'],
];
</script>

<template>
    <div class="flex flex-wrap gap-2" role="toolbar" aria-label="Map tools">
        <button v-for="[id, label] in tools" :key="id" type="button" class="rounded border px-2 py-1 text-sm" :class="tool === id ? 'border-brass text-brass-2' : 'border-line'" :aria-pressed="tool === id" @click="emit('select', id)">{{ label }}</button>
        <button type="button" class="rounded border border-line px-2 py-1 text-sm" @click="emit('zoom', -0.2)">Zoom out</button>
        <button type="button" class="rounded border border-line px-2 py-1 text-sm" @click="emit('zoom', 0.2)">Zoom in</button>
        <span class="self-center text-xs text-muted">{{ Math.round(scale * 100) }}%</span>
    </div>
</template>
