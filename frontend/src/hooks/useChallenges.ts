import { useState, useEffect, useCallback } from 'react'
import { challengeService } from '../api/challenge.service'
import type { Challenge, ChallengeFilters, Category } from '../types'

interface UseChallengesReturn {
  challenges: Challenge[]
  categories: Category[]
  pagination: { page: number; per_page: number; total: number; total_pages: number } | null
  isLoading: boolean
  error: string | null
  refetch: (filters?: ChallengeFilters) => Promise<void>
}

export function useChallenges(initial: ChallengeFilters = {}): UseChallengesReturn {
  const [challenges, setChallenges] = useState<Challenge[]>([])
  const [categories, setCategories] = useState<Category[]>([])
  const [pagination, setPagination] = useState<UseChallengesReturn['pagination']>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const refetch = useCallback(async (filters: ChallengeFilters = initial) => {
    setIsLoading(true); setError(null)
    try {
      const [list, cats] = await Promise.all([
        challengeService.list(filters),
        challengeService.categories(),
      ])
      setChallenges(list.challenges)
      setPagination(list.pagination)
      setCategories(cats.categories)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load challenges')
    } finally {
      setIsLoading(false)
    }
  }, [initial])

  useEffect(() => { refetch() }, [refetch])

  return { challenges, categories, pagination, isLoading, error, refetch }
}
