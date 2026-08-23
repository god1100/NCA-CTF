import type { FlagSubmissionInput, FlagSubmissionResult } from '../../types'

const MOCK_DELAY_MS = 800

export const mockFlagService = {
  submit: async (input: FlagSubmissionInput): Promise<FlagSubmissionResult> => {
    await new Promise(resolve => setTimeout(resolve, MOCK_DELAY_MS))
    const flag = input.flag.trim()
    if (!flag) {
      return { status: 'error', message: 'Flag cannot be empty.' }
    }
    if (!flag.startsWith('flag{') || !flag.endsWith('}')) {
      return { status: 'incorrect', message: 'Incorrect flag format. Flags should look like: flag{...}' }
    }
    const inner = flag.slice(5, -1)
    if (inner === 'correct') {
      return { status: 'correct', points: 250, firstBlood: true, message: 'First blood! +250 points' }
    }
    if (inner === 'solved') {
      return { status: 'already_solved', message: 'Your team has already solved this challenge.' }
    }
    if (inner === 'rate') {
      return { status: 'rate_limited', message: 'Too many attempts. Please wait before trying again.' }
    }
    if (inner === 'error') {
      return { status: 'error', message: 'A server error occurred. Please try again later.' }
    }
    return { status: 'incorrect', message: 'Incorrect flag. Try again.' }
  },
}
