<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const page = usePage()
const open = ref(false)
const permissions = computed(() => page.props.permissions ?? {})
const tenantOptions = computed(() => page.props.tenantOptions ?? [])
const tenant = computed(() => page.props.tenantContext)
const nav = computed(() => [
  { label: 'Dashboard', href: '/dashboard' },
  permissions.value['assets.view'] && { label: 'Aset', href: '/assets' },
  permissions.value['inventory.execute'] && { label: 'Inventarisasi', href: '/inventory' },
  permissions.value['approvals.view'] && { label: 'Persetujuan', href: '/approvals' },
  permissions.value['reports.view'] && { label: 'Pelaporan', href: '/reports' },
  permissions.value['tenant.settings.update'] && { label: 'Master data', href: '/master-data/locations' },
  permissions.value['audit.view'] && { label: 'Audit', href: '/administration/audit' },
  permissions.value['tenant.settings.update'] && { label: 'Administrasi', href: '/administration/members' },
].filter(Boolean))
const switchTenant = (event) => useForm({}).post(`/tenant/switch/${event.target.value}`)
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-950">
    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
        <div class="flex items-center gap-4"><Link href="/dashboard" class="text-lg font-bold tracking-tight text-[#0B2E5B]">DESATARA</Link><span class="hidden text-sm text-slate-500 sm:inline">Pengelolaan Aset Desa</span></div>
        <div class="flex items-center gap-3"><label class="sr-only" for="tenant-switcher">Desa aktif</label><select v-if="tenantOptions.length > 1" id="tenant-switcher" class="max-w-[12rem] rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm" :value="tenant?.uuid" @change="switchTenant"><option v-for="option in tenantOptions" :key="option.uuid" :value="option.uuid">{{ option.name }}</option></select><span v-else class="max-w-[12rem] truncate text-sm text-slate-600">{{ tenant?.name }}</span><Link href="/logout" method="post" as="button" class="hidden rounded-md px-2 py-1 text-sm text-slate-600 hover:bg-slate-100 sm:inline">Keluar</Link><button class="rounded-md border px-2 py-1 text-sm md:hidden" aria-label="Buka navigasi" @click="open = !open">Menu</button></div>
      </div>
      <nav :class="[open ? 'block' : 'hidden', 'border-t border-slate-100 md:block']" aria-label="Navigasi utama"><div class="mx-auto flex max-w-7xl flex-col gap-1 px-4 py-2 sm:px-6 md:flex-row md:items-center"> <Link v-for="item in nav" :key="item.href" :href="item.href" class="rounded-md px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">{{ item.label }}</Link><Link v-if="permissions['assets.view']" href="/search" class="rounded-md px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Cari</Link></div></nav>
    </header>
    <main><slot /></main>
  </div>
</template>
