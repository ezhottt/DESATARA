<script setup>
import { Head } from '@inertiajs/vue3';
import AppShell from '../Layouts/AppShell.vue';

defineOptions({ layout: AppShell });
defineProps({ tenant: Object, metrics: Object, canViewAssets: Boolean });
</script>

<template>
    <Head title="Dashboard" />
    <main class="min-h-screen bg-slate-50 text-slate-950">
        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-700">DESATARA</p>
            <h1 class="mt-2 text-3xl font-semibold">Dashboard {{ tenant.name }}</h1>
            <p class="mt-2 text-slate-600">Ringkasan yang perlu ditindaklanjuti pada tenant aktif.</p>
            <p v-if="!canViewAssets" class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Dashboard operasional terbatas. Minta permission aset pada administrator tenant untuk melihat metrik aset.</p>
            <section aria-label="Ringkasan tenant" class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <article v-for="(value, key) in metrics" :key="key" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm capitalize text-slate-600">{{ key }}</p>
                    <p class="mt-2 text-3xl font-semibold">{{ value }}</p>
                </article>
            </section>
        </div>
    </main>
</template>
