<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    product: { type: String, required: true },
});
</script>

<template>
    <Head title="Foundation" />
    <main class="min-h-screen bg-slate-50 text-slate-950">
        <div class="mx-auto flex min-h-screen max-w-5xl items-center px-6 py-16">
            <section class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-700">Platform Pengelolaan Aset Desa</p>
                <h1 class="mt-4 text-4xl font-semibold tracking-tight sm:text-5xl">{{ product }}</h1>
                <p class="mt-6 text-lg leading-8 text-slate-600">Repository & Runtime Foundation aktif. Modul domain belum diimplementasikan.</p>
            </section>
        </div>
    </main>
</template>
