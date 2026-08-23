import { useParams, useNavigate } from 'react-router-dom'
import { useChallenge } from '../hooks/useChallenge'
import { ChallengeFiles } from '../components/challenges/ChallengeFiles'
import { ChallengeHints } from '../components/challenges/ChallengeHints'
import { FlagSubmission } from '../components/challenges/FlagSubmission'
import { Card, CardHeader, CardTitle, CardContent } from '../components/ui/Card'
import { Badge } from '../components/ui/Badge'
import { Button } from '../components/ui/Button'
import { Spinner } from '../components/ui/Spinner'
import { ErrorState } from '../components/ui/ErrorState'
import { ArrowLeft, CheckCircle } from 'lucide-react'

export function ChallengeDetailPage() {
  const { identifier } = useParams<{ identifier: string }>()
  const navigate = useNavigate()
  const { challenge, isLoading, error, refetch } = useChallenge(identifier || '')

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Spinner />
      </div>
    )
  }

  if (error || !challenge) {
    return (
      <div className="space-y-4">
        <Button variant="ghost" onClick={() => navigate(-1)} leftIcon={<ArrowLeft className="w-4 h-4" />}>Back</Button>
        <ErrorState title="Challenge not found" description={error || 'The requested challenge could not be loaded.'} onRetry={refetch} />
      </div>
    )
  }

  const difficultyColor = {
    easy: 'success' as const, medium: 'warning' as const, hard: 'error' as const, insane: 'error' as const,
  }[challenge.difficulty.toLowerCase()] || 'default'

  return (
    <div className="space-y-6 max-w-4xl">
      <Button variant="ghost" onClick={() => navigate(-1)} leftIcon={<ArrowLeft className="w-4 h-4" />}>Back to Challenges</Button>

      <div>
        <div className="flex flex-wrap items-center gap-2 mb-3">
          {challenge.category && <Badge variant="info">{challenge.category}</Badge>}
          <Badge variant={difficultyColor}>{challenge.difficulty}</Badge>
          <Badge variant="accent">{challenge.points} pts</Badge>
          {challenge.solved && <Badge variant="success"><CheckCircle className="w-3 h-3 mr-1" /> Solved</Badge>}
        </div>
        <h1 className="text-2xl sm:text-3xl font-bold text-nca-text">{challenge.title}</h1>
        <div className="flex items-center gap-4 mt-2 text-sm text-nca-text-muted">
          <span className="capitalize">{challenge.deployment_type.replace('_', ' ')}</span>
          <span>Status: <span className="capitalize">{challenge.status}</span></span>
        </div>
      </div>

      <Card>
        <CardHeader><CardTitle className="text-base">Description</CardTitle></CardHeader>
        <CardContent>
          <div className="prose prose-invert prose-sm max-w-none">
            <p className="text-nca-text-muted whitespace-pre-wrap">{challenge.description}</p>
          </div>
        </CardContent>
      </Card>

      {challenge.id && <ChallengeFiles challengeId={challenge.id} />}
      {challenge.id && <ChallengeHints challengeId={challenge.id} />}
      {challenge.id && <FlagSubmission challengeId={challenge.id} />}
    </div>
  )
}
