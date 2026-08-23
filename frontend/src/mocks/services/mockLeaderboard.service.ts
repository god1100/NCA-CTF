import type { LeaderboardEntry } from '../../types'

const MOCK_ENTRIES: LeaderboardEntry[] = [
  { rank: 1, team: 'CyberNinjas', score: 3450, solved_count: 14, last_activity: '2024-01-15T14:30:00Z' },
  { rank: 2, team: 'ByteBusters', score: 3200, solved_count: 13, last_activity: '2024-01-15T13:45:00Z' },
  { rank: 3, team: 'NullPointers', score: 2950, solved_count: 12, last_activity: '2024-01-15T12:20:00Z' },
  { rank: 4, team: 'ShellShock', score: 2700, solved_count: 11, last_activity: '2024-01-15T11:10:00Z' },
  { rank: 5, team: 'RootForce', score: 2450, solved_count: 10, last_activity: '2024-01-15T10:05:00Z' },
  { rank: 6, team: 'PacketSniffers', score: 2100, solved_count: 9, last_activity: '2024-01-15T09:30:00Z' },
  { rank: 7, team: 'HashCrackers', score: 1850, solved_count: 8, last_activity: '2024-01-15T08:45:00Z' },
  { rank: 8, team: 'OverflowOps', score: 1600, solved_count: 7, last_activity: '2024-01-15T07:20:00Z' },
  { rank: 9, team: 'CipherSquad', score: 1350, solved_count: 6, last_activity: '2024-01-15T06:15:00Z' },
  { rank: 10, team: 'ExploitMasters', score: 1100, solved_count: 5, last_activity: '2024-01-15T05:00:00Z' },
]

const MOCK_DELAY_MS = 600

export const mockLeaderboardService = {
  getLeaderboard: async (): Promise<LeaderboardEntry[]> => {
    await new Promise(resolve => setTimeout(resolve, MOCK_DELAY_MS))
    return MOCK_ENTRIES
  },
}
