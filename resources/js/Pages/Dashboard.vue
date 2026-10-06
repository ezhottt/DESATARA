<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppShell from '../Layouts/AppShell.vue'
defineOptions({ layout: AppShell })
const props = defineProps({ tenant: Object, metrics: Object, canViewAssets: Boolean })
const definitions = {
  total_assets: ['Total aset', 'Aset tercatat pada register'],
  total_value: ['Nilai perolehan', 'Akumulasi nilai aset'],
  active_assets: ['Aset aktif', 'Aset yang masih digunakan'],
  unverified_assets: ['Belum diverifikasi', 'Perlu verifikasi data'],
  damaged_assets: ['Aset bermasalah', 'Kondisi rusak/rusak berat'],
  open_discrepancies: ['Selisih inventaris', 'Perlu rekonsiliasi'],
  pending_approvals: ['Menunggu persetujuan', 'Perlu keputusan'],
  active_inventory_sessions: ['Inventarisasi aktif', 'Sesi sedang berjalan'],
  pending_maintenance: ['Pemeliharaan', 'Terjadwal atau berjalan'],
  open_reports: ['Laporan terbuka', 'Belum difinalisasi'],
}
const actions = {
  unverified_assets: '/assets', damaged_assets: '/assets', open_discrepancies: '/inventory',
  pending_approvals: '/approvals', active_inventory_sessions: '/inventory',
  pending_maintenance: '/lifecycle/maintenance', open_reports: '/reports',
}
const format = (key, value) => key === 'total_value' ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value || 0)) : new Intl.NumberFormat('id-ID').format(Number(value || 0))
const attention = Object.entries(props.metrics ?? {}).filter(([key, value]) => actions[key] && Number(value) > 0)
</script>
<template><Head title="Dashboard" /><main class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:py-10">
<header class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Ringkasan Desa</p><h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">{{ tenant.name }}</h1><p class="mt-2 text-slate-600">Pantau kondisi aset dan pekerjaan yang perlu ditindaklanjuti.</p></div><Link v-if="canViewAssets" href="/assets" class="rounded-lg bg-[#0B2E5B] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-950">Buka register aset</Link></header>
<p v-if="!canViewAssets" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Akses dashboard operasional terbatas. Hubungi administrator desa untuk mendapatkan izin melihat data aset.</p>
<section aria-label="Ringkasan aset" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><article v-for="(value,key) in metrics" :key="key" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-600">{{ definitions[key]?.[0] || key }}</p><p :class="['mt-3 font-bold tracking-tight text-slate-950', key === 'total_value' ? 'text-2xl' : 'text-3xl']">{{ format(key,value) }}</p><p class="mt-2 text-xs leading-5 text-slate-500">{{ definitions[key]?.[1] || 'Ringkasan data desa' }}</p></article></section>
<section v-if="attention.length" class="rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold">Perlu perhatian</h2><p class="mt-1 text-sm text-slate-500">Prioritas operasional berdasarkan data terbaru.</p></div><div class="divide-y divide-slate-100"><Link v-for="[key,value] in attention" :key="key" :href="actions[key]" class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-slate-50"><div><p class="font-semibold">{{ definitions[key]?.[0] }}</p><p class="mt-1 text-sm text-slate-500">{{ definitions[key]?.[1] }}</p></div><span class="rounded-full bg-amber-50 px-3 py-1 text-sm font-bold text-amber-800">{{ format(key,value) }}</span></Link></div></section>
</main></template>