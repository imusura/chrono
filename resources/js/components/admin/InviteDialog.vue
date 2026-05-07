<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import { X } from 'lucide-vue-next'
import { useAdminRoles } from '@/composables/useAdminRoles'
import { invitationService } from '@/services/invitationService'
import { hmToDecimalHours } from '@/lib/format'
import { ValidationError } from '@/types'
import { useI18n } from 'vue-i18n'
import { toast } from 'vue-sonner'

const props = defineProps<{
  open: boolean
  organisationId?: number
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
}>()

const { t } = useI18n()
const orgIdRef = computed(() => props.organisationId)
const { query: rolesQuery } = useAdminRoles(orgIdRef)

const emailInput = ref('')
const emails = ref<string[]>([])
const contractedHoursHm = ref('8:00')
const isAdmin = ref(false)
const selectedRoleIds = ref<number[]>([])
const errors = ref<Record<string, string[]>>({})
const isPending = ref(false)

const reset = () => {
  emailInput.value = ''
  emails.value = []
  contractedHoursHm.value = '8:00'
  isAdmin.value = false
  selectedRoleIds.value = []
  errors.value = {}
}

watch(() => props.open, (open) => {
  if (!open) reset()
})

const addEmail = () => {
  const val = emailInput.value.trim()
  if (!val || emails.value.includes(val)) {
    emailInput.value = ''
    return
  }
  emails.value.push(val)
  emailInput.value = ''
}

const removeEmail = (email: string) => {
  emails.value = emails.value.filter((e) => e !== email)
}

const onEmailKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Enter' || e.key === ',') {
    e.preventDefault()
    addEmail()
  }
}

const submit = async () => {
  errors.value = {}

  if (emailInput.value.trim()) {
    addEmail()
  }

  if (!emails.value.length) {
    errors.value = { emails: [t('invite.dialog.emailRequired')] }
    return
  }

  isPending.value = true

  try {
    const result = await invitationService.store({
      emails: emails.value,
      role_ids: selectedRoleIds.value,
      contracted_hours: hmToDecimalHours(contractedHoursHm.value),
      is_admin: isAdmin.value,
      organisation_id: props.organisationId,
    })

    const sentCount = result.sent.length
    const skippedCount = result.skipped.length

    if (sentCount > 0) {
      toast.success(t('invite.dialog.successToast', { count: sentCount }))
    }
    if (skippedCount > 0) {
      toast.info(t('invite.dialog.skippedToast', { count: skippedCount }))
    }

    emit('update:open', false)
  } catch (error) {
    if (error instanceof ValidationError) {
      errors.value = error.errors
    }
  } finally {
    isPending.value = false
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-sm">
      <DialogHeader>
        <DialogTitle>{{ t('invite.dialog.title') }}</DialogTitle>
      </DialogHeader>

      <div class="grid gap-4 py-2">
        <div class="grid gap-1.5">
          <Label>{{ t('invite.dialog.emails') }}</Label>
          <div class="flex flex-wrap gap-1.5 rounded-md border px-3 py-2 min-h-10 focus-within:ring-1 focus-within:ring-ring">
            <span
              v-for="email in emails"
              :key="email"
              class="inline-flex items-center gap-1 rounded-full bg-secondary px-2 py-0.5 text-xs font-medium"
            >
              {{ email }}
              <button type="button" @click="removeEmail(email)" class="hover:text-destructive">
                <X class="h-3 w-3" />
              </button>
            </span>
            <input
              v-model="emailInput"
              type="email"
              :placeholder="emails.length ? '' : t('invite.dialog.emailPlaceholder')"
              class="flex-1 min-w-24 bg-transparent text-sm outline-none placeholder:text-muted-foreground"
              @keydown="onEmailKeydown"
              @blur="addEmail"
            />
          </div>
          <p class="text-xs text-muted-foreground">{{ t('invite.dialog.emailHint') }}</p>
          <p v-if="errors.emails" class="text-sm text-destructive">{{ errors.emails[0] }}</p>
        </div>

        <div class="grid gap-1.5">
          <Label for="invite-hours">{{ t('invite.dialog.contractedHours') }}</Label>
          <Input id="invite-hours" v-model="contractedHoursHm" placeholder="8:00" />
          <p v-if="errors.contracted_hours" class="text-sm text-destructive">{{ errors.contracted_hours[0] }}</p>
        </div>

        <div v-if="rolesQuery.data.value?.length" class="grid gap-2">
          <Label>{{ t('invite.dialog.role') }}</Label>
          <div class="space-y-2">
            <div
              v-for="role in rolesQuery.data.value"
              :key="role.id"
              class="flex items-center gap-2"
            >
              <Checkbox
                :id="`invite-role-${role.id}`"
                :model-value="selectedRoleIds.includes(role.id)"
                @update:model-value="(v) => v ? selectedRoleIds.push(role.id) : selectedRoleIds.splice(selectedRoleIds.indexOf(role.id), 1)"
              />
              <Label :for="`invite-role-${role.id}`" class="cursor-pointer flex items-center gap-2">
                <span class="h-3 w-3 rounded-full shrink-0" :style="{ backgroundColor: role.color }" />
                {{ role.name }}
              </Label>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <Label for="invite-admin" class="cursor-pointer">{{ t('invite.dialog.admin') }}</Label>
          <Switch id="invite-admin" v-model="isAdmin" />
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="$emit('update:open', false)">{{ t('common.cancel') }}</Button>
        <Button :disabled="isPending" @click="submit">
          {{ isPending ? t('invite.dialog.sending') : t('invite.dialog.send') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
