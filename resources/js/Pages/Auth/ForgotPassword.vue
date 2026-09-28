<script setup>
const brandLogo = '/logo/desatara-logo-horizontal.png'
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({ email: '' })
</script>

<template>
  <Head title="Lupa kata sandi" />
  <main class="flex min-h-screen items-center justify-center bg-slate-50 p-6">
    <form class="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200" @submit.prevent="form.post('/forgot-password')">
      <img :src="brandLogo" alt="DESATARA" class="h-16 w-auto object-contain object-left">
      <h1 class="mt-8 text-2xl font-semibold text-slate-900">Atur ulang kata sandi</h1>
      <p class="mt-2 text-sm text-slate-600">Masukkan email akun untuk menerima tautan pengaturan ulang kata sandi.</p>
      <label class="mt-6 block text-sm font-medium text-slate-700">Email
        <input v-model="form.email" type="email" required autocomplete="email" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
      </label>
      <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</p>
      <button :disabled="form.processing" class="mt-6 w-full rounded-lg bg-[#0B2E5B] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#09264b] disabled:opacity-50">Kirim tautan reset</button>
      <Link href="/login" class="mt-5 block text-center text-sm font-medium text-blue-700 hover:text-blue-800">Kembali ke halaman masuk</Link>
    </form>
  </main>
</template>
