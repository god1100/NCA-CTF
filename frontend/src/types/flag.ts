export type FlagSubmissionStatus =
  | 'idle'
  | 'submitting'
  | 'correct'
  | 'incorrect'
  | 'already_solved'
  | 'rate_limited'
  | 'error'

export interface FlagSubmissionInput {
  challengeId: number
  flag: string
}

export interface FlagSubmissionResult {
  status: FlagSubmissionStatus
  points?: number
  firstBlood?: boolean
  message?: string
}
