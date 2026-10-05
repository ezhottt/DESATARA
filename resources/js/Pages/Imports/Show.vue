<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppShell from '../../Layouts/AppShell.vue'
defineOptions({ layout: AppShell })
const props = defineProps({ job: Object })
const form = useForm({})
const commit = () => form.post(`/imports/${props.job.uuid}/commit`)
</script>
<template><Head title="Preview Impor" /><main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6">
<header><Link href="/imports" class="text-sm font-medium text-blue-700">â† Kembali ke impor</Link><h1 class="mt-3 text-3xl font-semibold">Preview Impor Aset</h1><p class="mt-2 text-slate-600">Periksa hasil validasi sebelum data ditulis ke register aset.</p></header>
<section class="grid gap-3 sm:grid-cols-4"><article v-for="[label,value] in [['Total',job.total_rows],['Valid',job.valid_rows],['Bermasalah',job.invalid_rows],['Terimpor',job.imported_rows]]" :key="label" class="rounded-xl border bg-white p-4"><p class="text-sm text-slate-500">{{ label }}</p><strong class="text-2xl">{{ value }}</strong></article></section>
<section v-if="job.errors?.length" class="rounded-xl border border-red-200 bg-red-50 p-4"><h2 class="font-semibold text-red-900">Data yang perlu diperbaiki</h2><p v-for="error in job.errors" :key="error.id" class="mt-2 text-sm text-red-800">Baris {{ job.rows.find(r => r.id === error.import_row_id)?.row_number }} Â· {{ error.field_name || 'baris' }} Â· {{ error.message }}</p></section>
<section class="overflow-x-auto rounded-xl border bg-white"><table class="w-full text-left text-sm"><thead class="bg-slate-50"><tr><th class="p-3">Baris</th><th class="p-3">Nama</th><th class="p-3">Kode</th><th class="p-3">Status</th></tr></thead><tbody><tr v-for="row in job.rows" :key="row.id" class="border-t"><td class="p-3">{{ row.row_number }}</td><td class="p-3">{{ row.normalized_payload?.name || row.raw_payload?.name || '-' }}</td><td class="p-3">{{ row.normalized_payload?.asset_code || row.raw_payload?.asset_code || '-' }}</td><td class="p-3">{{ row.status }}</td></tr></tbody></table></section>
<div v-if="job.status === 'previewed'" class="flex justify-end"><button :disabled="form.processing || (job.strategy === 'atomic' && job.invalid_rows > 0)" class="rounded-lg bg-blue-800 px-5 py-2.5 font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50" @click="commit">Commit ke Register Aset</button></div>
</main></template>