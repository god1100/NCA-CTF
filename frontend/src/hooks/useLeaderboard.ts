import { useState, useEffect, useCallback } from 'react'
import { leaderboardService } from '../api/leaderboard.service'
import type { LeaderboardEntry } from '../types'

export function useLeaderboard() {
  const [entries, setEntries] = useState<LeaderboardEntry[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const refetch = useCallback(async () => {
    setIsLoading(true); setError(null)
    try {
      const data = await leaderboardService.getLeaderboard()
      setEntries(data)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load leaderboard')
    } finally {
      setIsLoading(false)
    }
  }, [])

  useEffect(() => { refetch() }, [refetch])

  return { entries, isLoading, error, refetch }
}
