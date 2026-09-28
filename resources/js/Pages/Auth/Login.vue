<script setup>
import { Head, useForm } from '@inertiajs/vue3'
const form = useForm({ email: '', password: '' })
const submit = () => form.post('/login', { onFinish: () => form.reset('password') })
</script>
<template>
  <Head title="Masuk" />
  <main class="min-h-screen bg-slate-50 flex items-center justify-center p-6">
    <section class="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
      <p class="text-sm font-semibold text-blue-700">DESATARA</p>
      <h1 class="mt-2 text-2xl font-semibold text-slate-900">Masuk ke akun</h1>
      <p class="mt-2 text-sm text-slate-600">Platform Pengelolaan Aset Desa</p>
      <form class="mt-8 space-y-5" @submit.prevent="submit">
        <label class="block text-sm font-medium text-slate-700">Email
          <input v-model="form.email" type="email" autocomplete="username" required class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>
        <p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p>
        <label class="block text-sm font-medium text-slate-700">Kata sandi
          <input v-model="form.password" type="password" autocomplete="current-password" required class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>
        <button :disabled="form.processing" class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Masuk</button>
      </form>
    </section>
  </main>
</template>
