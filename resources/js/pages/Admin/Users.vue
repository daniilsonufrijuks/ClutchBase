<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });
defineProps<{ users: { data: { id: number; name: string; email: string; role: string; subscription?: { plan: string } | null }[] }; seo: SeoMeta }>();
function save(user: { id: number; role: string; subscription?: { plan: string } | null }) {
    useForm({ role: user.role, plan: user.subscription?.plan || 'free' }).put(`/admin/users/${user.id}`);
}
</script>

<template>
    <SeoHead :seo="seo" />
    <h1 class="font-display text-3xl">Users</h1>
    <ul class="mt-4 space-y-3 text-sm">
        <li v-for="user in users.data" :key="user.id" class="flex flex-wrap items-center gap-2 rounded border border-line p-3">
            <span class="w-48">{{ user.name }} · {{ user.email }}</span>
            <select v-model="user.role" class="rounded border border-line bg-panel px-2 py-1" aria-label="Role"><option>user</option><option>admin</option></select>
            <select :value="user.subscription?.plan || 'free'" class="rounded border border-line bg-panel px-2 py-1" aria-label="Plan" @change="user.subscription = { plan: ($event.target as HTMLSelectElement).value }"><option>free</option><option>premium</option></select>
            <button type="button" class="rounded border border-line px-2 py-1" @click="save(user)">Save</button>
        </li>
    </ul>
</template>
