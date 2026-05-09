<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Lightbulb, Bug, HelpCircle, Loader2 } from 'lucide-vue-next'
import { toast } from 'vue-sonner'
import { useFeedback } from '@/composables/useFeedback'
import type { FeedbackCategory } from '@/types'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog'

const props = defineProps<{
  open: boolean
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
}>()

const { t } = useI18n()
const route = useRoute()
const { isSubmitting, errors, submit } = useFeedback()

const category = ref<FeedbackCategory | null>(null)
const subject = ref('')
const description = ref('')
const showDetails = ref(false)

const categories = computed<{ value: FeedbackCategory; label: string; icon: typeof Lightbulb }[]>(() => [
  { value: 'suggestion', label: t('feedback.category_suggestion'), icon: Lightbulb },
  { value: 'bug', label: t('feedback.category_bug'), icon: Bug },
  { value: 'question', label: t('feedback.category_question'), icon: HelpCircle },
])

const canSubmit = computed(() =>
  category.value !== null && subject.value.trim().length > 0 && description.value.trim().length > 0,
)

const reset = () => {
  category.value = null
  subject.value = ''
  description.value = ''
  errors.value = {}
  showDetails.value = false
}

watch(() => props.open, (open) => {
  if (open) reset()
})

const handleSubmit = async () => {
  if (!canSubmit.value || !category.value) return

  const success = await submit({
    subject: subject.value.trim(),
    description: description.value.trim(),
    category: category.value,
    page: route.fullPath,
  })

  if (success) {
    toast.success(t('feedback.success'), { duration: 6000 })
    emit('update:open', false)
    return
  }

  if (Object.keys(errors.value).length === 0) {
    toast.error(t('feedback.error'))
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>{{ t('feedback.title') }}</DialogTitle>
        <DialogDescription>{{ t('feedback.subtitle') }}</DialogDescription>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="grid gap-4">
        <div class="grid grid-cols-3 gap-2">
          <button
            v-for="opt in categories"
            :key="opt.value"
            type="button"
            class="flex flex-col items-center justify-center gap-1.5 rounded-xl border p-3 transition-all duration-200 active:scale-[0.97]"
            :class="category === opt.value
              ? 'border-primary bg-primary/10 text-primary'
              : 'border-border bg-card hover:bg-accent text-foreground'"
            @click="category = opt.value"
          >
            <component :is="opt.icon" class="size-5" />
            <span class="text-xs font-medium">{{ opt.label }}</span>
          </button>
        </div>
        <p v-if="errors.category" class="text-sm text-destructive">{{ errors.category[0] }}</p>

        <div class="grid gap-1.5">
          <Label for="feedback-subject">{{ t('feedback.subject_label') }}</Label>
          <Input id="feedback-subject" v-model="subject" maxlength="255" autofocus />
          <p v-if="errors.subject" class="text-sm text-destructive">{{ errors.subject[0] }}</p>
        </div>

        <div class="grid gap-1.5">
          <Label for="feedback-description">{{ t('feedback.description_label') }}</Label>
          <Textarea id="feedback-description" v-model="description" rows="6" maxlength="5000" />
          <p v-if="errors.description" class="text-sm text-destructive">{{ errors.description[0] }}</p>
        </div>

        <p class="text-xs text-muted-foreground">
          {{ t('feedback.privacy_note') }}
          <button type="button" class="underline hover:text-foreground transition-colors" @click="showDetails = !showDetails">
            {{ t('feedback.what_gets_sent') }}
          </button>
        </p>

        <div v-if="showDetails" class="rounded-lg bg-muted/50 p-3 text-xs text-muted-foreground space-y-1 animate-in fade-in slide-in-from-top-1 duration-200">
          <p>{{ t('feedback.what_gets_sent_detail') }}</p>
        </div>

        <DialogFooter class="gap-2 sm:gap-0">
          <Button type="button" variant="ghost" :disabled="isSubmitting" @click="emit('update:open', false)">
            {{ t('common.cancel') }}
          </Button>
          <Button type="submit" :disabled="!canSubmit || isSubmitting">
            <Loader2 v-if="isSubmitting" class="mr-2 size-4 animate-spin" />
            {{ isSubmitting ? t('feedback.sending') : t('feedback.send') }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
