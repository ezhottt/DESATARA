<script setup>
const brandLogo = '/logo/desatara-logo-horizontal.png'
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({ email: '', password: '' })
const submit = () => form.post('/login', { onFinish: () => form.reset('password') })
</script>

<template>
  <Head title="Masuk" />
  <main class="flex min-h-screen items-center justify-center bg-slate-50 p-6">
    <section class="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
      <img :src="brandLogo" alt="DESATARA" class="h-16 w-auto object-contain object-left">
      <h1 class="mt-8 text-2xl font-semibold text-slate-900">Masuk ke akun</h1>
      <p class="mt-2 text-sm text-slate-600">Platform Pengelolaan Aset Desa</p>
      <form class="mt-8 space-y-5" @submit.prevent="submit">
        <label class="block text-sm font-medium text-slate-700">Email
          <input v-model="form.email" type="email" autocomplete="username" required class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>
        <p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p>
        <label class="block text-sm font-medium text-slate-700">Kata sandi
          <input v-model="form.password" type="password" autocomplete="current-password" required class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>
        <div class="text-right">
          <Link href="/forgot-password" class="text-sm font-medium text-blue-700 hover:text-blue-800">Lupa kata sandi?</Link>
        </div>
        <button :disabled="form.processing" class="w-full rounded-lg bg-[#0B2E5B] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#09264b] disabled:opacity-50">Masuk</button>
      </form>
    </section>
  </main>
</template>
