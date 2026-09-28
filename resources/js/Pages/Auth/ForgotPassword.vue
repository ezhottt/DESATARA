<script setup>
import { Head, useForm } from '@inertiajs/vue3'
const form = useForm({ email: '' })
</script>
<template><Head title="Lupa kata sandi" /><main class="min-h-screen bg-slate-50 flex items-center justify-center p-6"><form class="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200" @submit.prevent="form.post('/forgot-password')"><p class="text-sm font-semibold text-blue-700">DESATARA</p><h1 class="mt-2 text-2xl font-semibold">Atur ulang kata sandi</h1><label class="mt-6 block text-sm font-medium">Email<input v-model="form.email" type="email" required autocomplete="email" class="mt-2 w-full rounded-lg border px-3 py-2"></label><p v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</p><button :disabled="form.processing" class="mt-6 w-full rounded-lg bg-slate-900 px-4 py-2.5 text-white">Kirim tautan reset</button></form></main></template>
