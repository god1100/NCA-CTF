import { API_CONFIG } from '../config/api.config'
import { mockFlagService } from '../mocks/services/mockFlag.service'
import type { FlagSubmissionResult, FlagSubmissionInput } from '../types'

export const flagService = {
  submit: async (input: FlagSubmissionInput): Promise<FlagSubmissionResult> => {
    if (API_CONFIG.useMockFlagSubmission) {
      return mockFlagService.submit(input)
    }
    return {
      status: 'error',
      message: 'Flag submission is not yet available.',
    }
  },
}
