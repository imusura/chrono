export class ValidationError extends Error {
  errors: Record<string, string[]>

  constructor(errors: Record<string, string[]>) {
    super('Validation failed')
    this.errors = errors
  }
}
