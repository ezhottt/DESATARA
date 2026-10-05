<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const page = usePage()
const open = ref(false)
const permissions = computed(() => page.props.permissions ?? {})
const tenantOptions = computed(() => page.props.tenantOptions ?? [])
const tenant = computed(() => page.props.tenantContext)
const flash = computed(() => page.props.flash ?? {})
const nav = computed(() => [
  { label: 'Dashboard', href: '/dashboard' },
  permissions.value['assets.view'] && { label: 'Aset', href: '/assets' },
  permissions.value['inventory.execute'] && { label: 'Inventarisasi', href: '/inventory' },
  permissions.value['imports.view'] && { label: 'Impor Data', href: '/imports' },
  permissions.value['approvals.view'] && { label: 'Persetujuan', href: '/approvals' },
  permissions.value['reports.view'] && { label: 'Pelaporan', href: '/reports' },
  permissions.value['tenant.settings.update'] && { label: 'Master Data', href: '/master-data/locations' },
  permissions.value['audit.view'] && { label: 'Audit', href: '/administration/audit' },
  permissions.value['tenant.settings.update'] && { label: 'Administrasi', href: '/administration/members' },
].filter(Boolean))
const currentPath = computed(() => new URL(page.url, window.location.origin).pathname)
const active = (href) => href === '/dashboard' ? currentPath.value === href : currentPath.value.startsWith(href)
const switchTenant = (event) => useForm({}).post(`/tenant/switch/${event.target.value}`)
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-950">
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
      <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
        <div class="flex min-w-0 items-center gap-3">
          <Link href="/dashboard" class="shrink-0 text-lg font-extrabold tracking-tight text-[#0B2E5B]">DESATARA</Link>
          <span class="hidden truncate border-l border-slate-200 pl-3 text-sm text-slate-500 lg:inline">Pengelolaan Aset Desa</span>
        </div>
        <div class="flex min-w-0 items-center gap-2">
          <label class="sr-only" for="tenant-switcher">Desa aktif</label>
          <select v-if="tenantOptions.length > 1" id="tenant-switcher" class="max-w-[11rem] rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-sm" :value="tenant?.uuid" @change="switchTenant"><option v-for="option in tenantOptions" :key="option.uuid" :value="option.uuid">{{ option.name }}</option></select>
          <span v-else class="hidden max-w-[12rem] truncate rounded-full bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 sm:inline">{{ tenant?.name }}</span>
          <Link href="/logout" method="post" as="button" class="hidden rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100 sm:inline">Keluar</Link>
          <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold md:hidden" :aria-expanded="open" aria-label="Buka navigasi" @click="open = !open">{{ open ? 'Tutup' : 'Menu' }}</button>
        </div>
      </div>
      <nav :class="[open ? 'block' : 'hidden', 'border-t border-slate-100 md:block']" aria-label="Navigasi utama">
        <div class="mx-auto flex max-w-7xl flex-col gap-1 px-4 py-2 sm:px-6 md:flex-row md:items-center md:overflow-x-auto md:py-0">
          <Link v-for="item in nav" :key="item.href" :href="item.href" :aria-current="active(item.href) ? 'page' : undefined" :class="[active(item.href) ? 'border-blue-700 text-blue-800 md:border-b-2' : 'border-transparent text-slate-600 hover:text-slate-950', 'shrink-0 rounded-md border-l-2 px-3 py-2.5 text-sm font-semibold md:rounded-none md:border-l-0 md:border-b-2']">{{ item.label }}</Link>
          <Link v-if="permissions['assets.view']" href="/search" :class="[active('/search') ? 'border-blue-700 text-blue-800 md:border-b-2' : 'border-transparent text-slate-600 hover:text-slate-950', 'shrink-0 rounded-md border-l-2 px-3 py-2.5 text-sm font-semibold md:rounded-none md:border-l-0 md:border-b-2']">Cari</Link>
          <Link href="/logout" method="post" as="button" class="mt-2 rounded-md px-3 py-2.5 text-left text-sm font-semibold text-slate-600 hover:bg-slate-100 sm:hidden">Keluar</Link>
        </div>
      </nav>
    </header>
    <div v-if="flash.success || flash.error" class="mx-auto max-w-7xl px-4 pt-4 sm:px-6" role="status" aria-live="polite"><div :class="[flash.error ? 'border-red-200 bg-red-50 text-red-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900', 'rounded-xl border px-4 py-3 text-sm font-medium shadow-sm']">{{ flash.error || flash.success }}</div></div><main id="main-content"><slot /></main>
  </div>
</template>
