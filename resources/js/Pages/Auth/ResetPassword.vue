<script setup>
const brandLogo = '/logo/desatara-logo-horizontal.png'
import { Head, useForm } from '@inertiajs/vue3'

const props = defineProps({ email: String, token: String })
const form = useForm({ email: props.email ?? '', token: props.token ?? '', password: '', password_confirmation: '' })
</script>

<template>
  <Head title="Kata sandi baru" />
  <main class="flex min-h-screen items-center justify-center bg-slate-50 p-6">
    <form class="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200" @submit.prevent="form.post('/reset-password')">
      <img :src="brandLogo" alt="DESATARA" class="h-16 w-auto object-contain object-left">
      <h1 class="mt-8 text-2xl font-semibold text-slate-900">Kata sandi baru</h1>
      <p class="mt-2 text-sm text-slate-600">Gunakan kata sandi baru untuk akun DESATARA.</p>
      <label class="mt-6 block text-sm font-medium text-slate-700">Email
        <input v-model="form.email" type="email" required autocomplete="email" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
      </label>
      <label class="mt-4 block text-sm font-medium text-slate-700">Kata sandi baru
        <input v-model="form.password" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
      </label>
      <label class="mt-4 block text-sm font-medium text-slate-700">Konfirmasi kata sandi
        <input v-model="form.password_confirmation" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
      </label>
      <p v-if="form.errors.email || form.errors.password" class="mt-2 text-sm text-red-600">{{ form.errors.email || form.errors.password }}</p>
      <button :disabled="form.processing" class="mt-6 w-full rounded-lg bg-[#0B2E5B] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#09264b] disabled:opacity-50">Simpan kata sandi</button>
    </form>
  </main>
</template>
