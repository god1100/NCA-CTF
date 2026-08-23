export class ApiError extends Error {
  code: string
  status: number

  constructor(
    code: string,
    message: string,
    status: number = 500
  ) {
    super(message)
    this.name = 'ApiError'
    this.code = code
    this.status = status
  }
}

export function getUserFriendlyMessage(error: unknown): string {
  if (error instanceof ApiError) {
    switch (error.status) {
      case 400: return 'Invalid request. Please check your input.'
      case 401: return 'Your session has expired. Please sign in again.'
      case 403: return 'You do not have permission to perform this action.'
      case 404: return 'The requested resource was not found.'
      case 409: return 'This action conflicts with the current state.'
      case 422: return error.message || 'Validation failed. Please check your input.'
      case 429: return 'Too many requests. Please wait before trying again.'
      case 500: return 'A server error occurred. Please try again later.'
      default: return error.message || 'Something went wrong.'
    }
  }
  if (error instanceof TypeError && (error as Error).message.includes('fetch')) {
    return 'Network error. Please check your connection.'
  }
  return 'Something went wrong. Please try again.'
}
