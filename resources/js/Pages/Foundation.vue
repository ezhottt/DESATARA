<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    product: { type: String, required: true },
});

const page = usePage();
const tenants = computed(() => page.props.tenantOptions ?? []);
const user = computed(() => page.props.auth?.user ?? null);

const selectTenant = (tenant) => {
    router.post(`/tenant/switch/${tenant.uuid}`);
};
</script>

<template>
    <Head title="Pilih Desa" />
    <main class="min-h-screen bg-slate-50 text-slate-950">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-6 py-4">
                <span class="font-semibold tracking-tight text-[#0B2E5B]">DESATARA</span>
                <div class="flex items-center gap-3">
                    <div v-if="user" class="hidden text-right sm:block">
                        <p class="text-sm font-medium text-slate-800">{{ user.name }}</p>
                        <p class="text-xs text-slate-500">{{ user.email }}</p>
                    </div>
                    <Link href="/logout" method="post" as="button" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">
                        Keluar
                    </Link>
                </div>
            </div>
        </header>

        <div class="mx-auto flex min-h-[calc(100vh-73px)] max-w-5xl items-center px-6 py-16">
            <section class="w-full max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-700">Platform Pengelolaan Aset Desa</p>
                <h1 class="mt-4 text-4xl font-semibold tracking-tight sm:text-5xl">{{ product }}</h1>
                <p class="mt-6 text-lg leading-8 text-slate-600">Pilih desa aktif untuk masuk ke dashboard DESATARA dan mengelola aset sesuai kewenangan Anda.</p>

                <div v-if="tenants.length" class="mt-8 grid gap-3 sm:grid-cols-2">
                    <button v-for="tenant in tenants" :key="tenant.uuid" type="button" class="rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:border-blue-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2" @click="selectTenant(tenant)">
                        <span class="block text-xs font-semibold uppercase tracking-wider text-blue-700">Desa</span>
                        <span class="mt-2 block text-lg font-semibold text-slate-950">{{ tenant.name }}</span>
                        <span class="mt-2 block text-sm text-slate-500">Masuk ke dashboard &rarr;</span>
                    </button>
                </div>

                <div v-else class="mt-8 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-950">
                    <p class="font-semibold">Belum ada desa aktif untuk akun ini.</p>
                    <p class="mt-1 text-sm leading-6 text-amber-800">Hubungi administrator DESATARA untuk menambahkan keanggotaan desa.</p>
                </div>
            </section>
        </div>
    </main>
</template>
