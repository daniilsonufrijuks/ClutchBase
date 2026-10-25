<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });
defineProps<{ seo: SeoMeta }>();
const form = useForm({ email: '', password: '', remember: false });
</script>

<template>
    <SeoHead :seo="seo" />
    <form class="mx-auto max-w-md space-y-3 rounded-xl border border-line bg-panel p-6" @submit.prevent="form.post('/login')">
        <h1 class="font-display text-2xl">Log in</h1>
        <label class="block text-sm" for="email">Email</label>
        <input id="email" v-model="form.email" type="email" required class="w-full rounded border border-line bg-ink px-3 py-2" />
        <p v-if="form.errors.email" class="text-sm text-red-300">{{ form.errors.email }}</p>
        <label class="block text-sm" for="password">Password</label>
        <input id="password" v-model="form.password" type="password" required class="w-full rounded border border-line bg-ink px-3 py-2" />
        <label class="flex items-center gap-2 text-sm"><input v-model="form.remember" type="checkbox" /> Remember this browser</label>
        <button class="rounded bg-brass px-4 py-2 text-ink" type="submit">Log in</button>
        <p class="text-sm text-muted">No account yet? <Link href="/register" class="text-brass-2">Create one</Link></p>
    </form>
</template>
