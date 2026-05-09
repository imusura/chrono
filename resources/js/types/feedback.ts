export type FeedbackCategory = 'suggestion' | 'bug' | 'question'

export interface SubmitFeedbackPayload {
  subject: string
  description: string
  category: FeedbackCategory
  page: string
}
