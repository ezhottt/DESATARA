<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ query: String, assets: Object, locations: Array, responsible_parties: Array });
const query = ref(props.query);
const submit = () => router.get('/search', { q: query.value }, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Cari aset — DESATARA" />
    <main class="min-h-screen bg-slate-50 px-4 py-8 text-slate-950 sm:px-8">
        <div class="mx-auto max-w-6xl space-y-6">
            <header><Link href="/dashboard" class="text-sm font-semibold text-blue-700 hover:underline">← Dashboard</Link><h1 class="mt-3 text-3xl font-semibold">Cari aset</h1><p class="mt-2 text-slate-600">Pencarian hanya menampilkan aset pada desa aktif.</p></header>
            <form class="flex gap-3" role="search" @submit.prevent="submit"><label for="asset-search" class="sr-only">Cari nama, kode, atau nomor register</label><input id="asset-search" v-model="query" type="search" class="min-w-0 flex-1 rounded-md border border-slate-300 bg-white px-3 py-2" placeholder="Nama, kode, atau nomor register" /><button class="rounded-md bg-blue-800 px-4 py-2 font-semibold text-white hover:bg-blue-900" type="submit">Cari</button></form>
            <section aria-live="polite" class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <p v-if="!assets.data.length" class="p-6 text-slate-600">{{ query ? 'Tidak ada aset yang cocok.' : 'Belum ada aset.' }}</p>
                <div v-else class="divide-y divide-slate-200"><article v-for="asset in assets.data" :key="asset.uuid" class="p-5"><h2 class="font-semibold">{{ asset.name }}</h2><p class="mt-1 text-sm text-slate-600">{{ asset.asset_code || 'Tanpa kode' }} · {{ asset.register_number || 'Tanpa nomor register' }}</p><p class="mt-2 text-sm">{{ asset.condition }} · {{ asset.lifecycle_status }} · {{ asset.verification_status }}</p></article></div>
                <nav v-if="assets.links?.length > 3" aria-label="Pagination" class="flex flex-wrap gap-2 border-t border-slate-200 p-4"><Link v-for="link in assets.links" :key="link.label" :href="link.url || '#'" :aria-current="link.active ? 'page' : undefined" :aria-disabled="!link.url ? 'true' : undefined" :class="['rounded border px-3 py-1 text-sm', link.active ? 'bg-blue-800 text-white' : 'bg-white', !link.url && 'pointer-events-none opacity-50']">{{ link.label.replace(/&laquo;|&raquo;/g, '') }}</Link></nav>
            </section>
            <section v-if="locations.length || responsible_parties.length" aria-label="Hasil data terkait" class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-slate-200 bg-white p-5"><h2 class="font-semibold">Lokasi</h2><p v-for="location in locations" :key="location.id" class="mt-2 text-sm">{{ location.name }} <span class="text-slate-500">({{ location.code }})</span></p></div>
                <div class="rounded-lg border border-slate-200 bg-white p-5"><h2 class="font-semibold">Penanggung jawab</h2><p v-for="party in responsible_parties" :key="party.id" class="mt-2 text-sm">{{ party.party_type }}</p></div>
            </section>
        </div>
    </main>
</template>
