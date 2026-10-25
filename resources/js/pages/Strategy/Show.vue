<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import SeoHead from '@/components/SeoHead.vue';
import TacticalMapCanvas from '@/components/strategy/TacticalMapCanvas.vue';
import { formatTiming } from '@/lib/format';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SeoMeta, StrategyGraph } from '@/types';

defineOptions({ layout: AppLayout });
const props = defineProps<{ strategy: StrategyGraph; token: string; can_edit: boolean; seo: SeoMeta }>();
const form = useForm({});
</script>

<template>
    <SeoHead :seo="seo" />
    <p class="rounded border border-brass/40 bg-brass/10 p-3 text-sm">{{ strategy.disclaimer }}</p>
    <h1 class="mt-4 font-display text-3xl">{{ strategy.title }}</h1>
    <p class="text-sm text-muted">{{ strategy.map.name }} · {{ strategy.side.toUpperCase() }} · view only</p>
    <TacticalMapCanvas class="mt-4" :image="strategy.map.image" :license="strategy.map.license" :scale="1" tool="pan" :read-only="true" :players="strategy.players" :markers="strategy.markers" :paths="strategy.paths" :selected-slot="1" />
    <ol class="mt-4 space-y-2 text-sm">
        <li v-for="(step, index) in strategy.timeline" :key="index">{{ formatTiming(step.seconds) }} — {{ step.title }}. {{ step.instructions }}</li>
    </ol>
    <form v-if="$page.props.auth.user" class="mt-4" @submit.prevent="form.post(`/s/${props.token}/duplicate`)">
        <button class="rounded border border-line px-3 py-2 text-sm" type="submit">Duplicate into my playbook</button>
    </form>
    <p v-else class="mt-4 text-sm"><Link href="/login">Log in</Link> to copy this plan.</p>
</template>
