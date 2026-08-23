import { API_CONFIG } from '../config/api.config'
import { mockLeaderboardService } from '../mocks/services/mockLeaderboard.service'
import type { LeaderboardEntry } from '../types'

export const leaderboardService = {
  getLeaderboard: async (): Promise<LeaderboardEntry[]> => {
    if (API_CONFIG.useMockLeaderboard) {
      return mockLeaderboardService.getLeaderboard()
    }
    return []
  },
}
