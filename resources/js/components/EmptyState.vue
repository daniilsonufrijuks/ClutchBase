<script setup lang="ts">
defineProps<{ title: string; body: string }>();
</script>

<template>
    <div class="rounded-xl border border-dashed border-line bg-panel px-6 py-10 text-center">
        <h2 class="font-display text-lg">{{ title }}</h2>
        <p class="mx-auto mt-2 max-w-md text-sm text-muted">{{ body }}</p>
        <div class="mt-4"><slot /></div>
    </div>
</template>
