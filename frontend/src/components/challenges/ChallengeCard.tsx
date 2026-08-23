import { Link } from 'react-router-dom'
import { Card, CardContent, CardHeader, CardTitle } from '../ui/Card'
import { Badge } from '../ui/Badge'
import { Flag, CheckCircle } from 'lucide-react'
import type { Challenge } from '../../types'

interface ChallengeCardProps { challenge: Challenge }

export function ChallengeCard({ challenge }: ChallengeCardProps) {
  const difficultyColor = {
    easy: 'success' as const,
    medium: 'warning' as const,
    hard: 'error' as const,
    insane: 'error' as const,
  }[challenge.difficulty.toLowerCase()] || 'default'

  return (
    <Link to={`/challenges/${challenge.slug}`} className="block group">
      <Card className="h-full hover:border-nca-accent/30 hover:glow-accent transition-all duration-300">
        <CardHeader className="pb-3">
          <div className="flex items-start justify-between gap-2">
            <CardTitle className="text-base group-hover:text-nca-accent transition-colors">{challenge.title}</CardTitle>
            {challenge.solved && <CheckCircle className="w-5 h-5 text-nca-success shrink-0" />}
          </div>
          <div className="flex flex-wrap gap-2 mt-2">
            {challenge.category && <Badge variant="info">{challenge.category}</Badge>}
            <Badge variant={difficultyColor}>{challenge.difficulty}</Badge>
            <Badge variant="accent">{challenge.points} pts</Badge>
          </div>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-nca-text-muted line-clamp-2">{challenge.description}</p>
          <div className="flex items-center gap-2 mt-3 text-xs text-nca-text-dim">
            <Flag className="w-3.5 h-3.5" />
            <span className="capitalize">{challenge.deployment_type.replace('_', ' ')}</span>
          </div>
        </CardContent>
      </Card>
    </Link>
  )
}
