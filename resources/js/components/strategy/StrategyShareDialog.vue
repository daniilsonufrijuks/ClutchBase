<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import type { SharedProps } from '@/types';

const props = defineProps<{ strategyId: number }>();
const page = usePage<SharedProps>();
const form = useForm({ permission: 'view', expires_at: '' });
</script>

<template>
    <form class="rounded border border-line p-3 text-sm" @submit.prevent="form.post(`/strategies/${props.strategyId}/shares`)">
        <h2 class="font-display">Share</h2>
        <label class="mt-2 block">Access
            <select v-model="form.permission" class="mt-1 w-full rounded border border-line bg-ink px-2 py-1">
                <option value="view">View link</option>
                <option value="edit">Edit link</option>
            </select>
        </label>
        <button class="mt-3 rounded border border-line px-3 py-1" type="submit">Create link</button>
        <p v-if="page.props.flash.share_url" class="mt-2 break-all text-brass-2">{{ page.props.flash.share_url }}</p>
    </form>
</template>
