<script setup>
import { computed, onMounted, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import QRCode from 'qrcode'

const props = defineProps({
  labels: { type: Array, default: () => [] },
  tenant: { type: Object, required: true },
})

const presets = {
  small: { label: 'Small - 50 x 25 mm', width: '50mm', height: '25mm', qr: '15mm' },
  medium: { label: 'Medium - 70 x 35 mm', width: '70mm', height: '35mm', qr: '21mm' },
  large: { label: 'Large - 100 x 50 mm', width: '100mm', height: '50mm', qr: '29mm' },
}

const size = ref('medium')
const copies = ref(1)
const qrImages = ref({})
const activePreset = computed(() => presets[size.value])
const printableLabels = computed(() =>
  props.labels.flatMap((label) =>
    Array.from({ length: Math.max(1, Math.min(20, Number(copies.value) || 1)) }, (_, copy) => ({ ...label, copy }))
  )
)
const printLabels = () => window.print()

onMounted(async () => {
  const entries = await Promise.all(props.labels.map(async (label) => [
    label.uuid,
    await QRCode.toDataURL(label.qr_url, {
      errorCorrectionLevel: 'M',
      margin: 2,
      width: 320,
    }),
  ]))
  qrImages.value = Object.fromEntries(entries)
})
</script>

<template>
  <Head title="Preview label aset" />
  <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6">
    <header class="no-print flex flex-wrap items-end justify-between gap-4">
      <div>
        <Link href="/assets" class="text-sm font-semibold text-blue-700">&lt;- Kembali ke aset</Link>
        <p class="mt-5 text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Preview cetak</p>
        <h1 class="mt-2 text-3xl font-bold">Label Aset Desa</h1>
        <p class="mt-2 max-w-3xl text-slate-600">
          Preview dan hasil cetak memakai markup label yang sama. Preset ukuran adalah pilihan media operasional, bukan klaim ukuran regulasi.
        </p>
      </div>
      <button type="button" class="rounded-lg bg-[#0B2E5B] px-4 py-2.5 font-bold text-white" @click="printLabels">
        Cetak {{ printableLabels.length }} label
      </button>
    </header>

    <section class="no-print grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-2">
      <label class="text-sm font-semibold">
        Ukuran label
        <select v-model="size" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2">
          <option v-for="(preset, key) in presets" :key="key" :value="key">{{ preset.label }}</option>
        </select>
      </label>
      <label class="text-sm font-semibold">
        Jumlah salinan per aset
        <input v-model.number="copies" type="number" min="1" max="20" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2">
      </label>
    </section>

    <section class="no-print rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-950">
      QR menggunakan UUID publik aset yang stabil dan hanya membuka data verifikasi yang diizinkan. QR bukan pengganti kode inventaris administratif.
    </section>

    <section
      class="label-sheet"
      :style="{
        '--label-width': activePreset.width,
        '--label-height': activePreset.height,
        '--qr-size': activePreset.qr,
      }"
    >
      <article v-for="label in printableLabels" :key="label.uuid + '-' + label.copy" class="asset-label">
        <div class="label-copy">
          <strong class="village">PEMERINTAH DESA {{ label.village_name }}</strong>
          <strong class="asset-name">{{ label.name }}</strong>
          <span class="identity-title">Kode Inventaris</span>
          <strong class="inventory-code">{{ label.inventory_code }}</strong>
          <div class="metadata">
            <span>Tahun: <strong>{{ label.acquisition_year }}</strong></span>
            <span>NUP: <strong>{{ label.nup }}</strong></span>
          </div>
        </div>
        <div class="qr-wrap">
          <img v-if="qrImages[label.uuid]" :src="qrImages[label.uuid]" alt="QR verifikasi aset" class="qr">
          <small>Verifikasi</small>
        </div>
      </article>
    </section>
  </main>
</template>

<style scoped>
.label-sheet{display:flex;flex-wrap:wrap;align-content:flex-start;gap:3mm;background:#fff}
.asset-label{box-sizing:border-box;display:flex;width:var(--label-width);height:var(--label-height);align-items:center;gap:2mm;overflow:hidden;border:.25mm solid #111;padding:2mm;background:#fff;color:#000;break-inside:avoid}
.label-copy{min-width:0;display:flex;flex:1;flex-direction:column;line-height:1.18}
.village{font-size:6.5pt;letter-spacing:.02em}
.asset-name{margin:.7mm 0;font-size:9pt;line-height:1.08}
.identity-title{font-size:6pt}
.inventory-code{font-size:7.2pt;line-height:1.12;overflow-wrap:anywhere;word-break:break-word}
.metadata{display:flex;flex-wrap:wrap;gap:1.5mm;margin-top:.7mm;font-size:6.5pt}
.qr-wrap{display:flex;flex:none;flex-direction:column;align-items:center;justify-content:center;font-size:5.5pt}
.qr{width:var(--qr-size);height:var(--qr-size);object-fit:contain}
@page{size:A4;margin:8mm}
@media print{
  .no-print{display:none!important}
  main{max-width:none!important;margin:0!important;padding:0!important}
  .label-sheet{gap:2mm}
  .asset-label{break-inside:avoid;page-break-inside:avoid;box-shadow:none!important}
}
</style>
