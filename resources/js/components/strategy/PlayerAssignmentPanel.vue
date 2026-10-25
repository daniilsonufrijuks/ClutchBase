<script setup lang="ts">
import type { StrategyPlayer } from '@/types';

defineProps<{ players: StrategyPlayer[]; roles: Record<string, string>; selectedSlot: number; readOnly: boolean }>();
const emit = defineEmits<{ select: [slot: number] }>();
</script>

<template>
    <div class="space-y-3">
        <h2 class="font-display">Players</h2>
        <article v-for="player in players" :key="player.slot" class="rounded border border-line p-3 text-sm" :class="selectedSlot === player.slot ? 'border-brass' : ''">
            <button type="button" class="text-xs uppercase text-muted" @click="emit('select', player.slot)">Slot {{ player.slot }}</button>
            <label class="mt-2 block">Nickname <input v-model="player.nickname" :disabled="readOnly" class="mt-1 w-full rounded border border-line bg-ink px-2 py-1" /></label>
            <label class="mt-2 block">Role
                <select v-model="player.role" :disabled="readOnly" class="mt-1 w-full rounded border border-line bg-ink px-2 py-1">
                    <option v-for="(label, key) in roles" :key="key" :value="key">{{ label }}</option>
                </select>
            </label>
            <label class="mt-2 block">Responsibility <textarea v-model="player.responsibilities" :disabled="readOnly" class="mt-1 w-full rounded border border-line bg-ink px-2 py-1" rows="2" /></label>
            <label class="mt-2 block">Contingency <textarea v-model="player.contingency" :disabled="readOnly" class="mt-1 w-full rounded border border-line bg-ink px-2 py-1" rows="2" /></label>
        </article>
    </div>
</template>
