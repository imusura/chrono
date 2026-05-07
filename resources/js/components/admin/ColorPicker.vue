<script setup lang="ts">
import { computed } from 'vue'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  modelValue: string
  usedColors?: { color: string; label: string }[]
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const COLORS = [
  '#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16',
  '#22c55e', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9',
  '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef',
  '#ec4899', '#f43f5e', '#78716c', '#6b7280', '#475569',
  '#dc2626', '#ea580c', '#d97706', '#65a30d', '#16a34a',
  '#0891b2', '#2563eb', '#7c3aed', '#9333ea', '#db2777',
]

const { t } = useI18n()

const usedMap = computed(() => {
  const map = new Map<string, string>()
  for (const { color, label } of props.usedColors ?? []) {
    map.set(color.toLowerCase(), label)
  }
  return map
})

const isSelected = (c: string) => c.toLowerCase() === props.modelValue.toLowerCase()
const usedBy = (c: string) => usedMap.value.get(c.toLowerCase())
</script>

<template>
  <TooltipProvider :delay-duration="200">
    <div class="flex flex-wrap gap-1.5">
      <Tooltip v-for="c in COLORS" :key="c">
        <TooltipTrigger as-child>
          <button
            type="button"
            class="relative h-6 w-6 rounded-full border-2 transition-transform hover:scale-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            :class="isSelected(c) ? 'border-foreground scale-110' : 'border-transparent'"
            :style="{ backgroundColor: c }"
            @click="emit('update:modelValue', c)"
          >
            <span
              v-if="usedBy(c) && !isSelected(c)"
              class="pointer-events-none absolute inset-0 flex items-center justify-center rounded-full bg-black/30"
            >
              <span class="h-1.5 w-1.5 rounded-full bg-white/90" />
            </span>
          </button>
        </TooltipTrigger>
        <TooltipContent v-if="usedBy(c)" side="top" class="text-xs">
          {{ t('colorPicker.usedBy', { name: usedBy(c) }) }}
        </TooltipContent>
      </Tooltip>
    </div>
  </TooltipProvider>
</template>
