<script setup lang="ts">
import { ref } from 'vue';
import MovementPath from '@/components/strategy/MovementPath.vue';
import PlayerMarker from '@/components/strategy/PlayerMarker.vue';
import UtilityMarker from '@/components/strategy/UtilityMarker.vue';
import { clampUnit } from '@/lib/format';
import type { MapPoint, StrategyMarker, StrategyPath, StrategyPlayer } from '@/types';

const props = defineProps<{
    image: string;
    license: string;
    scale: number;
    tool: string;
    readOnly: boolean;
    players: StrategyPlayer[];
    markers: StrategyMarker[];
    paths: StrategyPath[];
    selectedSlot: number;
}>();

const emit = defineEmits<{
    place: [point: MapPoint];
    movePlayer: [slot: number, point: MapPoint];
    moveMarker: [index: number, point: MapPoint];
}>();

const board = ref<HTMLElement | null>(null);
const drag = ref<{ type: 'player' | 'marker'; index: number } | null>(null);

function pointFromEvent(event: PointerEvent): MapPoint {
    const rect = board.value!.getBoundingClientRect();
    return {
        x: clampUnit((event.clientX - rect.left) / rect.width),
        y: clampUnit((event.clientY - rect.top) / rect.height),
    };
}

function onBoardClick(event: PointerEvent) {
    if (props.readOnly || props.tool === 'pan' || drag.value) {
        return;
    }
    emit('place', pointFromEvent(event));
}

function startPlayer(slot: number, event: PointerEvent) {
    if (props.readOnly) {
        return;
    }
    drag.value = { type: 'player', index: slot };
    (event.target as HTMLElement).setPointerCapture?.(event.pointerId);
}

function startMarker(index: number, event: PointerEvent) {
    if (props.readOnly) {
        return;
    }
    drag.value = { type: 'marker', index };
}

function move(event: PointerEvent) {
    if (!drag.value) {
        return;
    }
    const point = pointFromEvent(event);
    if (drag.value.type === 'player') {
        emit('movePlayer', drag.value.index, point);
    } else {
        emit('moveMarker', drag.value.index, point);
    }
}

function stop() {
    drag.value = null;
}
</script>

<template>
    <div class="overflow-auto rounded-xl border border-line bg-ink" @pointermove="move" @pointerup="stop">
        <div class="relative" :style="{ width: `${scale * 100}%` }">
            <div ref="board" class="relative aspect-square" @pointerdown="onBoardClick">
                <img :src="image" alt="Tactical schematic" class="h-full w-full select-none object-contain" draggable="false" />
                <svg class="pointer-events-none absolute inset-0 h-full w-full" viewBox="0 0 1 1" aria-hidden="true">
                    <MovementPath v-for="(path, index) in paths" :key="index" :points="path.points" :label="path.label" />
                </svg>
                <PlayerMarker
                    v-for="player in players.filter((row) => row.start_x !== null && row.start_y !== null)"
                    :key="player.slot"
                    :slot="player.slot"
                    :nickname="player.nickname"
                    :role="player.role"
                    :selected="player.slot === selectedSlot"
                    :style="{ left: `${(player.start_x || 0) * 100}%`, top: `${(player.start_y || 0) * 100}%` }"
                    @pointerdown.stop="startPlayer(player.slot, $event)"
                />
                <UtilityMarker
                    v-for="(marker, index) in markers"
                    :key="`m-${index}`"
                    :kind="marker.kind"
                    :label="marker.label"
                    :style="{ left: `${marker.x * 100}%`, top: `${marker.y * 100}%` }"
                    @pointerdown.stop="startMarker(index, $event)"
                />
            </div>
        </div>
        <p class="px-3 py-2 text-xs text-muted">{{ license }}</p>
    </div>
</template>
