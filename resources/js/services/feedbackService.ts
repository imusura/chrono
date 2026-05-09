import api from '@/services/api'
import type { SubmitFeedbackPayload } from '@/types'

export const generateIdempotencyKey = (): string => {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID()
  }
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = Math.random() * 16 | 0
    const v = c === 'x' ? r : (r & 0x3) | 0x8
    return v.toString(16)
  })
}

export const feedbackService = {
  submitFeedback: async (payload: SubmitFeedbackPayload, idempotencyKey: string): Promise<void> => {
    await api.post('/feedback', payload, {
      headers: { 'Idempotency-Key': idempotencyKey },
    })
  },
}
