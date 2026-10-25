<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import type { SharedProps } from '@/types';

const page = usePage<SharedProps>();
const open = ref(false);
const searchOpen = ref(false);
const query = ref('');
const loading = ref(false);
const results = ref<{ name: string; href: string; meta: string }[]>([]);
let timer: number | undefined;

const links = [
    { href: '/skins', label: 'Skins' },
    { href: '/cases', label: 'Cases' },
    { href: '/collections', label: 'Collections' },
    { href: '/steam', label: 'Steam' },
    { href: '/strategies', label: 'Strategy Planner' },
    { href: '/guides', label: 'Guides' },
];

function onStart() {
    loading.value = true;
}
function onFinish() {
    loading.value = false;
}

onMounted(() => {
    router.on('start', onStart);
    router.on('finish', onFinish);
});
onUnmounted(() => {
    router.off?.('start', onStart);
});

async function searchInput() {
    window.clearTimeout(timer);
    timer = window.setTimeout(async () => {
        if (query.value.trim().length < 2) {
            results.value = [];
            return;
        }
        const response = await fetch(`/search/suggest?q=${encodeURIComponent(query.value)}`, {
            headers: { Accept: 'application/json' },
        });
        const payload = await response.json();
        results.value = payload.results ?? [];
    }, 180);
}

function submitSearch() {
    searchOpen.value = false;
    router.get('/search', { q: query.value });
}

function openResult(href: string) {
    searchOpen.value = false;
    router.visit(href);
}
</script>

<template>
    <div class="min-h-screen">
        <div class="h-0.5 bg-brass transition-all" :class="loading ? 'w-full' : 'w-0'" />
        <a href="#content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:bg-panel focus:px-3 focus:py-2">Skip to content</a>
        <header class="no-print sticky top-0 z-30 border-b border-line bg-ink/95 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center gap-4 px-4 py-3">
                <Link href="/" class="font-display text-lg tracking-tight">Clutch<span class="text-brass">Base</span></Link>
                <nav class="hidden items-center gap-4 text-sm text-muted lg:flex" aria-label="Primary">
                    <Link v-for="link in links" :key="link.href" :href="link.href" class="hover:text-text">{{ link.label }}</Link>
                </nav>
                <div class="ml-auto flex items-center gap-2">
                    <button type="button" class="rounded border border-line px-3 py-1.5 text-sm" @click="searchOpen = true">Search</button>
                    <Link v-if="page.props.auth.user" href="/account" class="hidden text-sm text-muted sm:inline">{{ page.props.auth.user.name }}</Link>
                    <template v-else>
                        <Link href="/login" class="text-sm text-muted">Log in</Link>
                        <Link href="/register" class="rounded bg-brass px-3 py-1.5 text-sm text-ink">Register</Link>
                    </template>
                    <button type="button" class="rounded border border-line px-2 py-1 lg:hidden" aria-label="Open menu" @click="open = !open">Menu</button>
                </div>
            </div>
            <nav v-if="open" class="border-t border-line px-4 py-3 lg:hidden" aria-label="Mobile">
                <Link v-for="link in links" :key="link.href" :href="link.href" class="block py-2 text-sm" @click="open = false">{{ link.label }}</Link>
                <Link v-if="!page.props.auth.user" href="/login" class="block py-2 text-sm" @click="open = false">Log in</Link>
                <Link v-if="!page.props.auth.user" href="/register" class="block py-2 text-sm" @click="open = false">Register</Link>
            </nav>
        </header>
        <div v-if="page.props.flash.success" class="mx-auto mt-4 max-w-6xl px-4">
            <p class="rounded border border-brass/40 bg-brass/10 px-3 py-2 text-sm" role="status">{{ page.props.flash.success }}</p>
        </div>
        <div v-if="page.props.flash.error" class="mx-auto mt-4 max-w-6xl px-4">
            <p class="rounded border border-red-400/40 bg-red-400/10 px-3 py-2 text-sm" role="alert">{{ page.props.flash.error }}</p>
        </div>
        <main id="content" class="mx-auto max-w-6xl px-4 py-8">
            <slot />
        </main>
        <footer class="no-print border-t border-line py-8 text-sm text-muted">
            <div class="mx-auto flex max-w-6xl flex-wrap justify-between gap-3 px-4">
                <p>ClutchBase catalog and planner. Demo rows are labeled and are not live market data.</p>
                <Link href="/guides/how-this-catalog-labels-data">Data labels</Link>
            </div>
        </footer>
        <div v-if="searchOpen" class="fixed inset-0 z-40 bg-ink/80 p-4" @keydown.esc="searchOpen = false">
            <div class="mx-auto mt-16 max-w-xl rounded-xl border border-line bg-panel p-4" role="dialog" aria-label="Search">
                <form @submit.prevent="submitSearch">
                    <label class="text-sm text-muted" for="global-search">Search the catalog</label>
                    <input id="global-search" v-model="query" autofocus class="mt-2 w-full rounded border border-line bg-ink px-3 py-2" @input="searchInput" />
                </form>
                <ul class="mt-3 divide-y divide-line">
                    <li v-for="result in results" :key="result.href">
                        <button type="button" class="flex w-full justify-between py-2 text-left text-sm" @click="openResult(result.href)">
                            <span>{{ result.name }}</span>
                            <span class="text-muted">{{ result.meta }}</span>
                        </button>
                    </li>
                </ul>
                <button type="button" class="mt-3 text-sm text-muted" @click="searchOpen = false">Close</button>
            </div>
        </div>
    </div>
</template>
