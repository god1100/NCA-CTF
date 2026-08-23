import { useState, useEffect, useCallback } from 'react'
import { challengeService } from '../api/challenge.service'
import type { Challenge } from '../types'

export function useChallenge(identifier: string) {
  const [challenge, setChallenge] = useState<Challenge | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const refetch = useCallback(async () => {
    setIsLoading(true); setError(null)
    try {
      const res = await challengeService.get(identifier)
      setChallenge(res.challenge)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load challenge')
    } finally {
      setIsLoading(false)
    }
  }, [identifier])

  useEffect(() => { refetch() }, [refetch])

  return { challenge, isLoading, error, refetch }
}
