<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { onMounted, ref } from 'vue'
import QRCode from 'qrcode'

const props = defineProps({ labels: { type: Array, default: () => [] }, tenant: { type: Object, required: true } })
const qrImages = ref({})
const printLabels = () => window.print()

onMounted(async () => {
  const entries = await Promise.all(props.labels.map(async (label) => [
    label.uuid,
    await QRCode.toDataURL(label.qr_url, { errorCorrectionLevel: 'M', margin: 1, width: 240 }),
  ]))
  qrImages.value = Object.fromEntries(entries)
})
</script>

<template>
  <Head title="Label identifikasi aset" />
  <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6">
    <header class="no-print flex flex-wrap items-end justify-between gap-4">
      <div>
        <Link href="/assets" class="text-sm font-semibold text-blue-700">&lt;- Kembali ke aset</Link>
        <p class="mt-5 text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Pengamanan & inventarisasi</p>
        <h1 class="mt-2 text-3xl font-bold">Label identifikasi aset</h1>
        <p class="mt-2 max-w-3xl text-slate-600">Identitas administratif pada label menggunakan kode barang dan NUP dari penatausahaan aset.</p>
      </div>
      <button type="button" class="rounded-lg bg-[#0B2E5B] px-4 py-2.5 font-bold text-white" @click="printLabels">Cetak {{ labels.length }} label</button>
    </header>

    <section class="no-print space-y-2 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
      <p><strong>Perhatian:</strong> proses menyiapkan label merotasi QR aktif. Label QR lama untuk aset yang dipilih tidak berlaku lagi.</p>
      <p><strong>Catatan kepatuhan:</strong> QR DESATARA adalah elemen tambahan untuk akses digital dan bukan pengganti kode barang maupun NUP resmi.</p>
      <p>Ukuran fisik label mengikuti kebutuhan media/printer desa; aplikasi tidak menetapkan ukuran stiker sebagai ketentuan regulasi.</p>
    </section>

    <section class="label-sheet">
      <article v-for="label in labels" :key="label.uuid" class="asset-label">
        <img v-if="qrImages[label.uuid]" :src="qrImages[label.uuid]" alt="" class="qr">
        <div class="label-copy">
          <strong class="tenant">{{ tenant.name }}</strong>
          <strong class="asset-name">{{ label.name }}</strong>
          <span>Kode barang: {{ label.item_code }}</span>
          <span>NUP: {{ label.nup }}</span>
          <small>QR DESATARA - akses digital tambahan</small>
        </div>
      </article>
    </section>
  </main>
</template>

<style scoped>
.label-sheet{display:grid;grid-template-columns:repeat(auto-fill,minmax(52mm,1fr));gap:3mm;background:white}
.asset-label{box-sizing:border-box;display:flex;min-height:32mm;align-items:center;gap:2.2mm;overflow:hidden;border:.3mm solid #111;padding:2.5mm;background:#fff;color:#111;break-inside:avoid}
.qr{width:21mm;height:21mm;flex:none}
.label-copy{min-width:0;display:flex;flex:1;flex-direction:column;font-size:7.5pt;line-height:1.25}
.tenant{font-size:7pt;text-transform:uppercase}
.asset-name{margin:.8mm 0;font-size:9pt;line-height:1.1}
.label-copy small{margin-top:1mm;font-size:5.5pt}
@page{size:A4;margin:8mm}
@media print{
  .no-print{display:none!important}
  main{max-width:none!important;padding:0!important;margin:0!important}
  .label-sheet{grid-template-columns:repeat(3,1fr);gap:2mm}
  .asset-label{page-break-inside:avoid}
}
</style>
