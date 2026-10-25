<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });
defineProps<{ seo: SeoMeta }>();
const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });
</script>

<template>
    <SeoHead :seo="seo" />
    <form class="mx-auto max-w-md space-y-3 rounded-xl border border-line bg-panel p-6" @submit.prevent="form.post('/register')">
        <h1 class="font-display text-2xl">Create account</h1>
        <label class="block text-sm" for="name">Name</label>
        <input id="name" v-model="form.name" required class="w-full rounded border border-line bg-ink px-3 py-2" />
        <p v-if="form.errors.name" class="text-sm text-red-300">{{ form.errors.name }}</p>
        <label class="block text-sm" for="email">Email</label>
        <input id="email" v-model="form.email" type="email" required class="w-full rounded border border-line bg-ink px-3 py-2" />
        <p v-if="form.errors.email" class="text-sm text-red-300">{{ form.errors.email }}</p>
        <label class="block text-sm" for="password">Password</label>
        <input id="password" v-model="form.password" type="password" required autocomplete="new-password" class="w-full rounded border border-line bg-ink px-3 py-2" />
        <p v-if="form.errors.password" class="text-sm text-red-300">{{ form.errors.password }}</p>
        <label class="block text-sm" for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" v-model="form.password_confirmation" type="password" required autocomplete="new-password" class="w-full rounded border border-line bg-ink px-3 py-2" />
        <button class="rounded bg-brass px-4 py-2 text-ink" type="submit" :disabled="form.processing">Create account</button>
        <p class="text-sm text-muted">Already registered? <Link href="/login" class="text-brass-2">Log in</Link></p>
    </form>
</template>
