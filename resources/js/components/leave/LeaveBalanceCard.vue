<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { Skeleton } from '@/components/ui/skeleton'
import { useLeaveBalance } from '@/composables/useLeaveBalance'
import { useLeaveTypes } from '@/composables/useLeaveTypes'
import { leaveColorFor } from '@/lib/leaveColors'
import { leaveIconFor } from '@/lib/dayIcons'

const { t } = useI18n()
const { data: balanceData, isPending: balancePending } = useLeaveBalance()
const { data: leaveTypes } = useLeaveTypes()

const items = computed(() => {
  const types = leaveTypes.value ?? []
  const balances = balanceData.value?.data ?? []
  const balanceById = new Map(balances.map((b) => [b.leave_type_id, b]))

  return types.map((type) => {
    const balance = balanceById.get(type.id)
    return {
      type,
      balance: balance?.balance ?? null,
      carryover: balance?.unexpired_carryover ?? 0,
      color: leaveColorFor(type.name),
      icon: leaveIconFor(type.name),
    }
  })
})
</script>

<template>
  <div class="bg-card border rounded-lg p-4 sm:p-5">
    <div class="text-sm font-medium text-muted-foreground mb-3">{{ t('leave.balance.title') }}</div>

    <Skeleton v-if="balancePending" class="h-16" />

    <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="item in items"
        :key="item.type.id"
        class="flex items-center gap-3 rounded border px-3 py-2.5"
      >
        <span class="size-9 rounded flex items-center justify-center" :class="item.color.swatch">
          <component :is="item.icon" class="size-4 text-foreground/70" />
        </span>
        <div class="flex-1 min-w-0">
          <div class="text-sm font-medium truncate">{{ item.type.name }}</div>
          <div v-if="item.type.has_allocation && item.balance !== null" class="text-xs text-muted-foreground tabular-nums">
            <span class="font-semibold text-foreground">{{ item.balance.toFixed(1) }}</span>
            {{ t('leave.balance.daysSuffix') }}
            <span v-if="item.carryover > 0" class="ml-1">
              · {{ t('leave.balance.carryover', { days: item.carryover.toFixed(1) }) }}
            </span>
          </div>
          <div v-else-if="!item.type.has_allocation" class="text-xs text-muted-foreground">
            {{ t('leave.balance.logOnly') }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
