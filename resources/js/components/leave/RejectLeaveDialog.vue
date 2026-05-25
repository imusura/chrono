<script setup lang="ts">
import { ref, watch } from 'vue'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { useI18n } from 'vue-i18n'

const props = defineProps<{ open: boolean; submitting?: boolean }>()
const emit = defineEmits<{
  'update:open': [value: boolean]
  confirm: [reason: string]
}>()

const { t } = useI18n()
const reason = ref('')

watch(
  () => props.open,
  (open) => {
    if (open) reason.value = ''
  },
)
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>{{ t('leave.rejectDialog.title') }}</DialogTitle>
      </DialogHeader>

      <div class="grid gap-2 py-2">
        <Label for="reject_reason">{{ t('leave.rejectDialog.reason') }}</Label>
        <Textarea id="reject_reason" v-model="reason" rows="3" :placeholder="t('leave.rejectDialog.placeholder')" />
      </div>

      <DialogFooter>
        <Button variant="outline" :disabled="submitting" @click="emit('update:open', false)">
          {{ t('leave.cancel') }}
        </Button>
        <Button
          variant="destructive"
          :disabled="!reason.trim() || submitting"
          @click="emit('confirm', reason.trim())"
        >
          {{ t('leave.reject') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
