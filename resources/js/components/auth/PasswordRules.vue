<script setup lang="ts">
import { computed } from 'vue'
import { Check, X } from 'lucide-vue-next'

const props = defineProps<{ password: string }>()

const rules = computed(() => [
  { label: 'At least 8 characters', ok: props.password.length >= 8 },
  { label: 'Upper and lower case letters', ok: /[a-z]/.test(props.password) && /[A-Z]/.test(props.password) },
  { label: 'At least one number', ok: /\d/.test(props.password) },
])
</script>

<template>
  <ul class="grid gap-1 text-xs">
    <li v-for="rule in rules" :key="rule.label" class="flex items-center gap-2">
      <Check v-if="rule.ok" class="size-3.5 text-emerald-600" />
      <X v-else class="size-3.5 text-muted-foreground" />
      <span :class="rule.ok ? 'text-emerald-600' : 'text-muted-foreground'">{{ rule.label }}</span>
    </li>
  </ul>
</template>
