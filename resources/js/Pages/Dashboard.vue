<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({ tenant: Object, metrics: Object });
</script>

<template>
    <Head title="Dashboard" />
    <main class="min-h-screen bg-slate-50 text-slate-950">
        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-700">DESATARA</p>
            <h1 class="mt-2 text-3xl font-semibold">Dashboard {{ tenant.name }}</h1>
            <p class="mt-2 text-slate-600">Ringkasan yang perlu ditindaklanjuti pada tenant aktif.</p>
            <section aria-label="Ringkasan tenant" class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <article v-for="(value, key) in metrics" :key="key" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm capitalize text-slate-600">{{ key }}</p>
                    <p class="mt-2 text-3xl font-semibold">{{ value }}</p>
                </article>
            </section>
        </div>
    </main>
</template>
