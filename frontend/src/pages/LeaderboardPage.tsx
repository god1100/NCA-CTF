import { useLeaderboard } from '../hooks/useLeaderboard'
import { LeaderboardTable } from '../components/leaderboard/LeaderboardTable'
import { Spinner } from '../components/ui/Spinner'
import { EmptyState } from '../components/ui/EmptyState'
import { ErrorState } from '../components/ui/ErrorState'
import { Alert } from '../components/ui/Alert'
import { Trophy } from 'lucide-react'

export function LeaderboardPage() {
  const { entries, isLoading, error, refetch } = useLeaderboard()

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-nca-text">Leaderboard</h1>
        <p className="text-sm text-nca-text-muted mt-1">Top teams ranked by score.</p>
      </div>

      <Alert variant="info">
        <div className="flex items-center gap-2">
          <Trophy className="w-4 h-4" />
          <span>Leaderboard data is currently simulated for UI development. Real rankings will appear once Phase 6 is implemented.</span>
        </div>
      </Alert>

      {isLoading ? (
        <div className="flex items-center justify-center py-20"><Spinner /></div>
      ) : error ? (
        <ErrorState title="Failed to load leaderboard" description={error} onRetry={refetch} />
      ) : entries.length === 0 ? (
        <EmptyState title="No rankings yet" description="The leaderboard is empty. Be the first to score!" />
      ) : (
        <LeaderboardTable entries={entries} />
      )}
    </div>
  )
}
