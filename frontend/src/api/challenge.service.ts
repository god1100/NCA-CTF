import { api } from './client'
import type { Challenge, ChallengeListResponse, Category, ChallengeFile, ChallengeHint, ChallengeFilters } from '../types'

export const challengeService = {
  categories: () =>
    api.get<{ categories: Category[] }>('/categories'),

  list: (filters: ChallengeFilters = {}) => {
    const params = new URLSearchParams()
    if (filters.page) params.set('page', String(filters.page))
    if (filters.per_page) params.set('per_page', String(filters.per_page))
    if (filters.category) params.set('category', filters.category)
    if (filters.difficulty) params.set('difficulty', filters.difficulty)
    const query = params.toString()
    return api.get<ChallengeListResponse>(`/challenges${query ? '?' + query : ''}`)
  },

  get: (identifier: string) =>
    api.get<{ challenge: Challenge }>(`/challenges/${identifier}`),

  files: (challengeId: number) =>
    api.get<{ files: ChallengeFile[] }>(`/challenges/${challengeId}/files`),

  hints: (challengeId: number) =>
    api.get<{ hints: ChallengeHint[] }>(`/challenges/${challengeId}/hints`),

  revealHint: (hintId: number) =>
    api.post<{ hint: ChallengeHint }>(`/challenge-hints/${hintId}/reveal`),
}
