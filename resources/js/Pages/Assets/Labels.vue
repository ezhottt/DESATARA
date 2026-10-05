<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed, onMounted, ref } from 'vue'
import QRCode from 'qrcode'

const props = defineProps({ labels: { type: Array, default: () => [] }, tenant: { type: Object, required: true } })
const size = ref('50x30')
const qrImages = ref({})
const printLabels = () => window.print()
const dimensions = computed(() => size.value === '60x40' ? { width: '60mm', height: '40mm' } : { width: '50mm', height: '30mm' })

onMounted(async () => {
  const entries = await Promise.all(props.labels.map(async (label) => [
    label.uuid,
    await QRCode.toDataURL(label.qr_url, { errorCorrectionLevel: 'M', margin: 1, width: 240 }),
  ]))
  qrImages.value = Object.fromEntries(entries)
})
</script>

<template>
  <Head title="Cetak label aset" />
  <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6">
    <header class="no-print flex flex-wrap items-end justify-between gap-4">
      <div>
        <Link href="/assets" class="text-sm font-semibold text-blue-700">&lt;- Kembali ke aset</Link>
        <p class="mt-5 text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Identifikasi aset</p>
        <h1 class="mt-2 text-3xl font-bold">Cetak label aset</h1>
        <p class="mt-2 text-slate-600">QR lama pada aset yang dipilih telah diganti. Cetak dan pasang label baru ini.</p>
      </div>
      <div class="flex flex-wrap items-end gap-3">
        <label class="text-sm font-semibold">Ukuran label
          <select v-model="size" class="mt-1 block rounded-lg border px-3 py-2">
            <option value="50x30">50 x 30 mm</option>
            <option value="60x40">60 x 40 mm</option>
          </select>
        </label>
        <button type="button" class="rounded-lg bg-[#0B2E5B] px-4 py-2.5 font-bold text-white" @click="printLabels">Cetak {{ labels.length }} label</button>
      </div>
    </header>

    <section class="no-print rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
      <strong>Perhatian:</strong> proses menyiapkan label merotasi QR aktif. Label QR lama untuk aset yang dipilih tidak berlaku lagi.
    </section>

    <section class="label-sheet">
      <article v-for="label in labels" :key="label.uuid" class="asset-label" :style="{ width: dimensions.width, height: dimensions.height }">
        <img v-if="qrImages[label.uuid]" :src="qrImages[label.uuid]" alt="" class="qr">
        <div class="label-copy">
          <strong class="tenant">{{ tenant.name }}</strong>
          <strong class="asset-name">{{ label.name }}</strong>
          <span>{{ label.asset_code || 'Tanpa kode aset' }}</span>
          <span>Reg: {{ label.register_number || '-' }}</span>
        </div>
      </article>
    </section>
  </main>
</template>

<style scoped>
.label-sheet{display:flex;flex-wrap:wrap;align-content:flex-start;gap:4mm;background:white}
.asset-label{box-sizing:border-box;display:flex;align-items:center;gap:2.2mm;overflow:hidden;border:.3mm solid #111;padding:2mm;background:#fff;color:#111;break-inside:avoid}
.qr{width:20mm;height:20mm;flex:none}
.label-copy{min-width:0;display:flex;flex:1;flex-direction:column;font-size:7.5pt;line-height:1.2}
.tenant{font-size:7pt;text-transform:uppercase}
.asset-name{margin:.8mm 0;font-size:9pt;line-height:1.1}
@page{size:A4;margin:8mm}
@media print{
  .no-print{display:none!important}
  main{max-width:none!important;padding:0!important;margin:0!important}
  .label-sheet{gap:2mm}
  .asset-label{page-break-inside:avoid}
}
</style>
