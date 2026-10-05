<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppShell from '../../Layouts/AppShell.vue'

defineOptions({ layout: AppShell })

const props = defineProps({
  asset: Object,
  classifications: { type: Array, default: () => [] },
  locations: { type: Array, default: () => [] },
  units: { type: Array, default: () => [] },
  fundingSources: { type: Array, default: () => [] },
})

const classificationQuery = ref('')
const filteredClassifications = computed(() => {
  const q = classificationQuery.value.trim().toLowerCase()
  if (!q) return props.classifications
  return props.classifications.filter((item) =>
    [item.code, item.name].some((value) => String(value || '').toLowerCase().includes(q))
  )
})

const form = useForm({
  classification_id: props.asset?.classification_id || '',
  asset_code: props.asset?.asset_code || '',
  name: props.asset?.name || '',
  description: props.asset?.description || '',
  acquisition_date: props.asset?.acquisition_date || '',
  quantity: props.asset?.quantity || 1,
  unit_id: props.asset?.unit_id || '',
  acquisition_value: props.asset?.acquisition_value || '',
  current_location_id: props.asset?.current_location_id || '',
  condition: props.asset?.condition || 'good',
  lock_version: props.asset?.lock_version,
})

const submit = () => form[props.asset ? 'patch' : 'post'](props.asset ? `/assets/${props.asset.uuid}` : '/assets')
</script>

<template>
  <Head :title="asset ? 'Edit aset' : 'Tambah aset baru'" />
  <main class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6">
    <header>
      <Link href="/assets" class="text-sm font-medium text-blue-700">&lt;- Kembali ke daftar aset</Link>
      <h1 class="mt-3 text-3xl font-bold tracking-tight">{{ asset ? 'Edit informasi aset' : 'Tambah aset baru' }}</h1>
      <p class="mt-2 text-slate-600">
        Nama spesifik aset disimpan pada record ini. Kode barang tetap berasal dari master klasifikasi.
      </p>
    </header>

    <form class="grid gap-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:grid-cols-2" @submit.prevent="submit">
      <label class="text-sm font-medium md:col-span-2">
        Nama barang spesifik
        <input v-model="form.name" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Contoh: ASUS ExpertBook B1402">
      </label>

      <div class="space-y-2 md:col-span-2">
        <label for="classification-search" class="text-sm font-medium">Cari master klasifikasi</label>
        <input id="classification-search" v-model="classificationQuery" type="search" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Cari kode barang atau nama barang baku">
        <label class="block text-sm font-medium">
          Klasifikasi / kode barang
          <select v-model="form.classification_id" required :disabled="Boolean(asset)" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 disabled:bg-slate-100">
            <option value="">Pilih klasifikasi</option>
            <option v-for="item in filteredClassifications" :key="item.id" :value="item.id">{{ item.code }} - {{ item.name }}</option>
          </select>
        </label>
        <p class="text-xs text-slate-500">Kode barang tidak diketik bebas; referensinya berasal dari master klasifikasi DESATARA.</p>
      </div>

      <label class="text-sm font-medium">
        Kode internal (opsional)
        <input v-model="form.asset_code" :disabled="Boolean(asset)" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 disabled:bg-slate-100">
      </label>

      <div class="text-sm font-medium">
        NUP
        <div class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-slate-700">
          {{ asset?.register_number || 'NUP dibuat otomatis saat aset disimpan' }}
        </div>
      </div>

      <label class="text-sm font-medium">
        Tanggal perolehan
        <input v-model="form.acquisition_date" type="date" required :disabled="Boolean(asset)" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 disabled:bg-slate-100">
        <span class="mt-1 block text-xs font-normal text-slate-500">Tahun kode inventaris diturunkan dari tanggal ini.</span>
      </label>

      <label class="text-sm font-medium">
        Jumlah
        <input v-model="form.quantity" type="number" min="1" max="1" step="1" required :disabled="Boolean(asset)" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 disabled:bg-slate-100">
        <span class="mt-1 block text-xs font-normal text-slate-500">Registrasi interaktif memakai 1 record untuk 1 unit fisik.</span>
      </label>

      <label class="text-sm font-medium">
        Satuan
        <select v-model="form.unit_id" required :disabled="Boolean(asset)" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 disabled:bg-slate-100">
          <option value="">Pilih unit</option>
          <option v-for="item in units" :key="item.id" :value="item.id">{{ item.code }} - {{ item.name }}</option>
        </select>
      </label>

      <label class="text-sm font-medium">
        Nilai perolehan (Rp)
        <input v-model="form.acquisition_value" type="number" min="0" step="0.01" :disabled="Boolean(asset)" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 disabled:bg-slate-100">
      </label>

      <label class="text-sm font-medium">
        Lokasi
        <select v-model="form.current_location_id" :disabled="Boolean(asset)" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 disabled:bg-slate-100">
          <option value="">Belum ditentukan</option>
          <option v-for="item in locations" :key="item.id" :value="item.id">{{ item.code }} - {{ item.name }}</option>
        </select>
      </label>

      <label class="text-sm font-medium">
        Kondisi
        <select v-model="form.condition" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
          <option value="good">Baik</option>
          <option value="fair">Rusak ringan</option>
          <option value="damaged">Rusak</option>
          <option value="broken">Rusak berat</option>
        </select>
      </label>

      <label class="text-sm font-medium md:col-span-2">
        Deskripsi
        <textarea v-model="form.description" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
      </label>

      <p v-if="form.errors && Object.keys(form.errors).length" class="text-sm text-red-700 md:col-span-2">
        Periksa kembali input yang ditandai server.
      </p>

      <button :disabled="form.processing" class="rounded-lg bg-[#0B2E5B] px-5 py-2.5 font-bold text-white disabled:opacity-50 md:col-span-2">
        {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
      </button>
    </form>
  </main>
</template>
