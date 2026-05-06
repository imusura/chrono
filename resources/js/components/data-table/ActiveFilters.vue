<script setup lang="ts">
import { X } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'

defineProps<{
  filters: { key: string; label: string; value: string }[]
}>()

const emit = defineEmits<{
  remove: [key: string]
  clear: []
}>()
</script>

<template>
  <div v-if="filters.length > 0" class="flex flex-wrap items-center gap-2">
    <Badge
      v-for="filter in filters"
      :key="filter.key"
      variant="secondary"
      class="gap-1 pr-1"
    >
      <span class="text-muted-foreground">{{ filter.label }}:</span>
      {{ filter.value }}
      <button
        class="ml-0.5 rounded-full p-0.5 hover:bg-foreground/10"
        @click="emit('remove', filter.key)"
      >
        <X class="size-3" />
      </button>
    </Badge>
    <button
      v-if="filters.length > 1"
      class="text-xs text-muted-foreground hover:text-foreground"
      @click="emit('clear')"
    >
      Clear all
    </button>
  </div>
</template>
