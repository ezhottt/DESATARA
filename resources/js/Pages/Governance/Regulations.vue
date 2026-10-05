<script setup>
import AppShell from '../../Layouts/AppShell.vue'
defineOptions({ layout: AppShell })
defineProps({ rules: Array })
</script>
<template><main class="mx-auto max-w-6xl p-6"><h1 class="text-2xl font-semibold">Regulatory Traceability</h1><p class="mt-2 text-slate-600">Trace regulation â†’ provision â†’ business rule â†’ system contract.</p><div v-for="rule in rules" :key="rule.id" class="mt-4 rounded border p-4"><strong>{{ rule.code }} â€” {{ rule.name }}</strong><div class="text-sm text-slate-600">{{ rule.jurisdiction_level }} Â· {{ rule.status }} Â· {{ rule.provisions_count }} provision Â· {{ rule.bindings_count }} binding</div></div><p v-if="!rules.length" class="mt-6 text-slate-500">Belum ada regulatory rule.</p></main></template>
