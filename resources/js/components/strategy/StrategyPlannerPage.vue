<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ExecutionTimeline from '@/components/strategy/ExecutionTimeline.vue';
import MapToolbar from '@/components/strategy/MapToolbar.vue';
import PlayerAssignmentPanel from '@/components/strategy/PlayerAssignmentPanel.vue';
import StrategyShareDialog from '@/components/strategy/StrategyShareDialog.vue';
import TacticalMapCanvas from '@/components/strategy/TacticalMapCanvas.vue';
import UtilitySequenceEditor from '@/components/strategy/UtilitySequenceEditor.vue';
import type { MapPoint, StrategyGraph, StrategyMarker } from '@/types';

const props = defineProps<{
    strategy: StrategyGraph;
    canEdit: boolean;
    roles: Record<'t' | 'ct', Record<string, string>>;
    objectives: Record<string, string>;
    utilityNames: Record<string, string>;
    maps: { id: number; name: string }[];
    folders: { id: number; name: string }[];
    guides: { title: string; slug: string }[];
}>();

const form = useForm({
    revision: props.strategy.revision,
    title: props.strategy.title,
    map_asset_id: props.strategy.map.id,
    folder_id: props.strategy.folder_id,
    side: props.strategy.side,
    objective: props.strategy.objective,
    summary: props.strategy.summary,
    visibility: props.strategy.visibility,
    players: props.strategy.players,
    markers: props.strategy.markers,
    paths: props.strategy.paths,
    utility: props.strategy.utility,
    timeline: props.strategy.timeline,
});

const tool = ref('player');
const scale = ref(1);
const selectedSlot = ref(1);
const past = ref<string[]>([]);
const future = ref<string[]>([]);
const readOnly = computed(() => !props.canEdit);
const roleOptions = computed(() => props.roles[form.side] || props.roles.t);

function snapshot(): string {
    return JSON.stringify({
        players: form.players,
        markers: form.markers,
        paths: form.paths,
        utility: form.utility,
        timeline: form.timeline,
    });
}

function begin() {
    past.value.push(snapshot());
    if (past.value.length > 40) {
        past.value.shift();
    }
    future.value = [];
}

function restore(raw: string) {
    const data = JSON.parse(raw);
    form.players = data.players;
    form.markers = data.markers;
    form.paths = data.paths;
    form.utility = data.utility;
    form.timeline = data.timeline;
}

function undo() {
    const previous = past.value.pop();
    if (!previous) {
        return;
    }
    future.value.push(snapshot());
    restore(previous);
}

function redo() {
    const next = future.value.pop();
    if (!next) {
        return;
    }
    past.value.push(snapshot());
    restore(next);
}

function place(point: MapPoint) {
    if (readOnly.value) {
        return;
    }
    begin();
    if (tool.value === 'player') {
        const player = form.players.find((row) => row.slot === selectedSlot.value);
        if (player) {
            player.start_x = point.x;
            player.start_y = point.y;
        }
        return;
    }
    if (tool.value === 'path') {
        const existing = form.paths.find((path) => path.player_slot === selectedSlot.value && path.label === 'Draft path');
        if (existing) {
            existing.points.push(point);
        } else {
            form.paths.push({ player_slot: selectedSlot.value, label: 'Draft path', points: [point, point] });
        }
        return;
    }
    const kind = tool.value === 'label' ? 'label' : tool.value;
    const marker: StrategyMarker = { kind, player_slot: selectedSlot.value, x: point.x, y: point.y, label: kind, step_number: form.markers.length + 1 };
    form.markers.push(marker);
    if (['smoke', 'flash', 'he', 'molotov'].includes(kind)) {
        form.utility.push({
            player_slot: selectedSlot.value,
            utility_type: kind,
            throw_x: null,
            throw_y: null,
            land_x: point.x,
            land_y: point.y,
            timing_seconds: 0,
            step_number: marker.step_number,
            notes: '',
            lineup_reference: null,
        });
    }
}

function movePlayer(slot: number, point: MapPoint) {
    const player = form.players.find((row) => row.slot === slot);
    if (player) {
        player.start_x = point.x;
        player.start_y = point.y;
    }
}

function removeMarker(index: number) {
    begin();
    form.markers.splice(index, 1);
}

function onKey(event: KeyboardEvent) {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'z') {
        event.preventDefault();
        undo();
    }
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'y') {
        event.preventDefault();
        redo();
    }
}
</script>

<template>
    <div @keydown="onKey">
        <p class="rounded border border-brass/40 bg-brass/10 p-3 text-sm">{{ strategy.disclaimer }}</p>
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <input v-model="form.title" :disabled="readOnly" class="font-display text-2xl bg-transparent" aria-label="Strategy title" />
            <div class="flex flex-wrap gap-2 text-sm">
                <button type="button" class="rounded border border-line px-2 py-1" @click="undo">Undo</button>
                <button type="button" class="rounded border border-line px-2 py-1" @click="redo">Redo</button>
                <Link :href="`/strategies/${strategy.id}/print`">Print</Link>
                <a :href="`/strategies/${strategy.id}/export`">Export</a>
                <button v-if="canEdit" type="button" class="rounded bg-brass px-3 py-1 text-ink" :disabled="form.processing" @click="form.put(`/strategies/${strategy.id}`)">Save</button>
            </div>
        </div>
        <p v-if="form.errors.revision" class="mt-2 text-sm text-red-300">{{ form.errors.revision }}</p>
        <div class="mt-4 grid gap-6 xl:grid-cols-[280px_1fr_300px]">
            <PlayerAssignmentPanel :players="form.players" :roles="roleOptions" :selected-slot="selectedSlot" :read-only="readOnly" @select="selectedSlot = $event" />
            <div>
                <MapToolbar :tool="tool" :scale="scale" @select="tool = $event" @zoom="scale = Math.min(2.4, Math.max(0.8, scale + $event))" />
                <TacticalMapCanvas
                    class="mt-3"
                    :image="strategy.map.image"
                    :license="strategy.map.license"
                    :scale="scale"
                    :tool="tool"
                    :read-only="readOnly"
                    :players="form.players"
                    :markers="form.markers"
                    :paths="form.paths"
                    :selected-slot="selectedSlot"
                    @place="place"
                    @move-player="movePlayer"
                    @move-marker="(index, point) => { form.markers[index].x = point.x; form.markers[index].y = point.y }"
                />
                <button v-if="canEdit && form.markers.length" type="button" class="mt-2 text-sm text-muted" @click="removeMarker(form.markers.length - 1)">Remove last marker</button>
                <label class="mt-4 block text-sm">Summary <textarea v-model="form.summary" :disabled="readOnly" class="mt-1 w-full rounded border border-line bg-panel px-3 py-2" rows="3" /></label>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    <label class="text-sm">Side
                        <select v-model="form.side" :disabled="readOnly" class="mt-1 w-full rounded border border-line bg-panel px-2 py-2"><option value="t">Terrorist</option><option value="ct">Counter-Terrorist</option></select>
                    </label>
                    <label class="text-sm">Objective
                        <select v-model="form.objective" :disabled="readOnly" class="mt-1 w-full rounded border border-line bg-panel px-2 py-2"><option v-for="(label, key) in objectives" :key="key" :value="key">{{ label }}</option></select>
                    </label>
                    <label class="text-sm">Visibility
                        <select v-model="form.visibility" :disabled="readOnly" class="mt-1 w-full rounded border border-line bg-panel px-2 py-2"><option value="private">Private</option><option value="team">Team</option><option value="link">Link access</option></select>
                    </label>
                    <label class="text-sm">Map
                        <select v-model="form.map_asset_id" :disabled="readOnly" class="mt-1 w-full rounded border border-line bg-panel px-2 py-2"><option v-for="map in maps" :key="map.id" :value="map.id">{{ map.name }}</option></select>
                    </label>
                </div>
            </div>
            <div class="space-y-6">
                <UtilitySequenceEditor :actions="form.utility" :names="utilityNames" :read-only="readOnly" @remove="(index) => { begin(); form.utility.splice(index, 1) }" />
                <ExecutionTimeline :steps="form.timeline" :read-only="readOnly" @add="() => { begin(); form.timeline.push({ seconds: 0, title: 'New step', instructions: '' }) }" @remove="(index) => { begin(); form.timeline.splice(index, 1) }" />
                <StrategyShareDialog v-if="canEdit" :strategy-id="strategy.id" />
                <div>
                    <h2 class="font-display text-sm">Guides</h2>
                    <ul class="mt-2 text-sm"><li v-for="guide in guides" :key="guide.slug"><Link :href="`/guides/${guide.slug}`">{{ guide.title }}</Link></li></ul>
                </div>
                <form v-if="canEdit" @submit.prevent="useForm({}).post(`/strategies/${strategy.id}/archive`)"><button class="text-sm text-muted" type="submit">Archive or restore</button></form>
                <form v-if="canEdit" @submit.prevent="useForm({}).post(`/strategies/${strategy.id}/duplicate`)"><button class="text-sm text-muted" type="submit">Duplicate</button></form>
            </div>
        </div>
    </div>
</template>
