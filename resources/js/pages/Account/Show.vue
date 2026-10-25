<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import SeoHead from '@/components/SeoHead.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SeoMeta } from '@/types';

defineOptions({ layout: AppLayout });
const props = defineProps<{ preferences: Record<string, unknown>; seo: SeoMeta }>();
const form = useForm({ display_name: '', currency: 'USD' });
const deletion = useForm({ password: '' });
</script>

<template>
    <SeoHead :seo="seo" />
    <h1 class="font-display text-3xl">Account</h1>
    <nav class="mt-4 flex flex-wrap gap-3 text-sm">
        <Link href="/account/favorites">Favorites</Link>
        <Link href="/account/wishlists">Wishlists</Link>
        <Link href="/account/alerts">Price alerts</Link>
        <Link href="/strategies">Playbook</Link>
        <Link v-if="$page.props.auth.user?.role === 'admin'" href="/admin">Admin</Link>
    </nav>
    <form class="mt-6 max-w-md space-y-3" @submit.prevent="form.patch('/account')">
        <label class="block text-sm" for="display_name">Display name</label>
        <input id="display_name" v-model="form.display_name" class="w-full rounded border border-line bg-panel px-3 py-2" />
        <label class="block text-sm" for="currency">Currency label</label>
        <select id="currency" v-model="form.currency" class="w-full rounded border border-line bg-panel px-3 py-2">
            <option>USD</option><option>EUR</option><option>GBP</option>
        </select>
        <button class="rounded bg-brass px-3 py-2 text-ink" type="submit">Save preferences</button>
    </form>
    <form class="mt-10 max-w-md space-y-3 border-t border-line pt-6" @submit.prevent="deletion.delete('/account')">
        <h2 class="font-display text-xl">Delete account</h2>
        <p class="text-sm text-muted">This removes your favorites, wishlists, and teams you own.</p>
        <label class="block text-sm" for="delete-password">Current password</label>
        <input id="delete-password" v-model="deletion.password" type="password" required class="w-full rounded border border-line bg-panel px-3 py-2" />
        <button class="rounded border border-red-300 px-3 py-2 text-sm" type="submit">Delete account</button>
    </form>
</template>
