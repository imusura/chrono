<script setup lang="ts">
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

const props = defineProps<{
  currentPage: number
  lastPage: number
  from: number | null
  to: number | null
  total: number
}>()

const emit = defineEmits<{
  page: [page: number]
}>()
</script>

<template>
  <div class="flex items-center justify-between px-2 py-4">
    <div class="text-sm text-muted-foreground">
      <template v-if="from && to">
        Showing {{ from }}-{{ to }} of {{ total }}
      </template>
      <template v-else>
        No results
      </template>
    </div>
    <div class="flex items-center gap-1">
      <Button variant="outline" size="icon-sm" :disabled="currentPage <= 1" @click="emit('page', 1)">
        <ChevronsLeft class="size-4" />
      </Button>
      <Button variant="outline" size="icon-sm" :disabled="currentPage <= 1" @click="emit('page', currentPage - 1)">
        <ChevronLeft class="size-4" />
      </Button>
      <span class="px-3 text-sm">
        Page {{ currentPage }} of {{ lastPage }}
      </span>
      <Button variant="outline" size="icon-sm" :disabled="currentPage >= lastPage"
        @click="emit('page', currentPage + 1)">
        <ChevronRight class="size-4" />
      </Button>
      <Button variant="outline" size="icon-sm" :disabled="currentPage >= lastPage" @click="emit('page', lastPage)">
        <ChevronsRight class="size-4" />
      </Button>
    </div>
  </div>
</template>
