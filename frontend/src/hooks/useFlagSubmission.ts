import { useState, useCallback } from 'react'
import { flagService } from '../api/flag.service'
import type { FlagSubmissionResult, FlagSubmissionStatus } from '../types'

export function useFlagSubmission() {
  const [result, setResult] = useState<FlagSubmissionResult | null>(null)
  const [status, setStatus] = useState<FlagSubmissionStatus>('idle')
  const [isSubmitting, setIsSubmitting] = useState(false)

  const submit = useCallback(async (challengeId: number, flag: string) => {
    setIsSubmitting(true); setStatus('submitting')
    try {
      const res = await flagService.submit({ challengeId, flag })
      setResult(res); setStatus(res.status)
    } catch (err) {
      setResult({ status: 'error', message: err instanceof Error ? err.message : 'Submission failed.' })
      setStatus('error')
    } finally {
      setIsSubmitting(false)
    }
  }, [])

  const reset = useCallback(() => { setResult(null); setStatus('idle') }, [])

  return { result, status, isSubmitting, submit, reset }
}
