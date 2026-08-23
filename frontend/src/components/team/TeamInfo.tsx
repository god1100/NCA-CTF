import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '../ui/Card'
import { Badge } from '../ui/Badge'
import { Crown, Users } from 'lucide-react'
import type { Team } from '../../types'

interface TeamInfoProps { team: Team; isCaptain: boolean }

export function TeamInfo({ team, isCaptain }: TeamInfoProps) {
  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between">
          <div>
            <CardTitle className="text-xl">{team.name}</CardTitle>
            <CardDescription>Team ID: {team.id}</CardDescription>
          </div>
          {isCaptain && <Badge variant="accent"><Crown className="w-3 h-3 mr-1" /> Captain</Badge>}
        </div>
      </CardHeader>
      <CardContent>
        <div className="flex items-center gap-2 text-sm text-nca-text-muted">
          <Users className="w-4 h-4" />
          <span>Status: <span className="capitalize">{team.status}</span></span>
        </div>
      </CardContent>
    </Card>
  )
}
