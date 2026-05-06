<script setup lang="ts">
import { ArrowUpDown, ArrowUp, ArrowDown } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

const props = defineProps<{
  label: string
  field: string
  currentSort?: string
  currentDirection?: string
}>()

const emit = defineEmits<{
  sort: [field: string]
}>()

const isActive = () => props.currentSort === props.field
</script>

<template>
  <Button
    variant="ghost"
    size="sm"
    class="-ml-3 h-8"
    @click="emit('sort', field)"
  >
    {{ label }}
    <ArrowUp v-if="isActive() && currentDirection === 'asc'" class="ml-1 size-3.5" />
    <ArrowDown v-else-if="isActive() && currentDirection === 'desc'" class="ml-1 size-3.5" />
    <ArrowUpDown v-else class="ml-1 size-3.5 text-muted-foreground/50" />
  </Button>
</template>
