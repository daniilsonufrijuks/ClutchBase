<script setup lang="ts">
import { formatTiming } from '@/lib/format';
import type { UtilityAction } from '@/types';

defineProps<{ actions: UtilityAction[]; names: Record<string, string>; readOnly: boolean }>();
const emit = defineEmits<{ remove: [index: number] }>();
</script>

<template>
    <div>
        <h2 class="font-display">Utility sequence</h2>
        <p v-if="!actions.length" class="mt-2 text-sm text-muted">No utility yet. Choose a grenade tool and click the schematic.</p>
        <ol class="mt-3 space-y-3">
            <li v-for="(action, index) in [...actions].sort((a, b) => a.timing_seconds - b.timing_seconds)" :key="index" class="rounded border border-line p-3 text-sm">
                <p>{{ formatTiming(action.timing_seconds) }} · slot {{ action.player_slot }} · {{ names[action.utility_type] || action.utility_type }}</p>
                <label class="mt-2 block">Seconds <input v-model.number="action.timing_seconds" :disabled="readOnly" type="number" min="0" max="180" class="mt-1 w-24 rounded border border-line bg-ink px-2 py-1" /></label>
                <label class="mt-2 block">Notes <textarea v-model="action.notes" :disabled="readOnly" class="mt-1 w-full rounded border border-line bg-ink px-2 py-1" rows="2" /></label>
                <p class="mt-1 text-xs text-muted">{{ action.lineup_reference || 'No verified lineup guide is linked.' }}</p>
                <button v-if="!readOnly" type="button" class="mt-2 text-xs text-muted" @click="emit('remove', actions.indexOf(action))">Remove</button>
            </li>
        </ol>
    </div>
</template>
