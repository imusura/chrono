import { ref } from 'vue'
import axios from 'axios'
import { feedbackService, generateIdempotencyKey } from '@/services/feedbackService'
import type { SubmitFeedbackPayload } from '@/types'
import { ValidationError } from '@/types'

export const useFeedback = () => {
  const isSubmitting = ref(false)
  const errors = ref<Record<string, string[]>>({})

  const submit = async (payload: SubmitFeedbackPayload): Promise<boolean> => {
    errors.value = {}
    isSubmitting.value = true
    const idempotencyKey = generateIdempotencyKey()

    const attempt = (): Promise<void> =>
      feedbackService.submitFeedback(payload, idempotencyKey)

    try {
      try {
        await attempt()
      } catch (error) {
        // Retry once on 503. Same idempotency key, so ticketing replays safely if the first attempt succeeded.
        if (axios.isAxiosError(error) && error.response?.status === 503) {
          await attempt()
        } else {
          throw error
        }
      }
      return true
    } catch (error) {
      if (error instanceof ValidationError) {
        errors.value = error.errors
      }
      return false
    } finally {
      isSubmitting.value = false
    }
  }

  return { isSubmitting, errors, submit }
}
