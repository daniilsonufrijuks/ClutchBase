<script setup lang="ts">
import type { MapPoint } from '@/types';

defineProps<{ points: MapPoint[]; label: string | null }>();
</script>

<template>
    <polyline :points="points.map((point) => `${point.x},${point.y}`).join(' ')" fill="none" stroke="#d7a35a" stroke-width="0.008" />
</template>
