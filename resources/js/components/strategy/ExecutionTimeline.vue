<script setup lang="ts">
import { formatTiming } from '@/lib/format';
import type { TimelineStep } from '@/types';

defineProps<{ steps: TimelineStep[]; readOnly: boolean }>();
const emit = defineEmits<{ add: []; remove: [index: number] }>();
</script>

<template>
    <div>
        <div class="flex items-center justify-between">
            <h2 class="font-display">Timeline</h2>
            <button v-if="!readOnly" type="button" class="text-sm text-brass-2" @click="emit('add')">Add step</button>
        </div>
        <ol class="mt-3 space-y-3">
            <li v-for="(step, index) in [...steps].sort((a, b) => a.seconds - b.seconds)" :key="index" class="rounded border border-line p-3 text-sm">
                <label>Seconds <input v-model.number="step.seconds" :disabled="readOnly" type="number" min="0" max="180" class="ml-2 w-20 rounded border border-line bg-ink px-2 py-1" /></label>
                <p class="mt-1 text-xs text-muted">{{ formatTiming(step.seconds) }}</p>
                <input v-model="step.title" :disabled="readOnly" class="mt-2 w-full rounded border border-line bg-ink px-2 py-1" aria-label="Step title" />
                <textarea v-model="step.instructions" :disabled="readOnly" class="mt-2 w-full rounded border border-line bg-ink px-2 py-1" rows="2" aria-label="Step instructions" />
                <button v-if="!readOnly" type="button" class="mt-2 text-xs text-muted" @click="emit('remove', steps.indexOf(step))">Remove</button>
            </li>
        </ol>
    </div>
</template>
