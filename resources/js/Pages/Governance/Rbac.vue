<script setup>
import AppShell from '../../Layouts/AppShell.vue'
defineOptions({ layout: AppShell })
defineProps({ roles: Array, permissions: Array })
</script>
<template><main class="mx-auto max-w-6xl p-6"><h1 class="text-2xl font-semibold">RBAC</h1><p class="mt-2 text-slate-600">Role aplikasi dan permission teknis. Role tidak menentukan kewenangan regulatif.</p><div class="mt-6 grid gap-6 md:grid-cols-2"><section><h2 class="font-semibold">Role</h2><div v-for="role in roles" :key="role.id" class="mt-2 rounded border p-3">{{ role.name }} Â· {{ role.scope_type }} Â· {{ role.permissions_count }} permission</div></section><section><h2 class="font-semibold">Permission</h2><div v-for="permission in permissions" :key="permission.id" class="mt-2 rounded border p-3 font-mono text-sm">{{ permission.code }}</div></section></div></main></template>
