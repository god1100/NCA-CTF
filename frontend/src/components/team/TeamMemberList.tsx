import { Card, CardHeader, CardTitle, CardContent } from '../ui/Card'
import { Button } from '../ui/Button'
import { Badge } from '../ui/Badge'
import { Crown, UserX, CrownIcon } from 'lucide-react'
import type { TeamMember } from '../../types'

interface TeamMemberListProps { members: TeamMember[]; isCaptain: boolean; onRemove: (userId: number) => void; onTransfer: (userId: number) => void }

export function TeamMemberList({ members, isCaptain, onRemove, onTransfer }: TeamMemberListProps) {
  return (
    <Card>
      <CardHeader><CardTitle className="text-base">Members ({members.length})</CardTitle></CardHeader>
      <CardContent className="space-y-2">
        {members.map(member => (
          <div key={member.user_id} className="flex items-center justify-between p-3 rounded-lg bg-nca-bg border border-nca-border">
            <div className="flex items-center gap-3">
              <div className="w-8 h-8 rounded-full bg-nca-accent/10 border border-nca-accent/30 flex items-center justify-center">
                <span className="text-xs font-bold text-nca-accent">{(member.username || member.full_name || 'U').charAt(0).toUpperCase()}</span>
              </div>
              <div>
                <p className="text-sm font-medium text-nca-text">{member.username || member.full_name || 'Unknown'}</p>
                {member.is_captain && <Badge variant="accent" className="mt-0.5"><Crown className="w-3 h-3 mr-1" /> Captain</Badge>}
              </div>
            </div>
            {isCaptain && !member.is_captain && (
              <div className="flex items-center gap-2">
                <Button size="sm" variant="ghost" onClick={() => onTransfer(member.user_id)} leftIcon={<CrownIcon className="w-3.5 h-3.5" />}>Make Captain</Button>
                <Button size="sm" variant="danger" onClick={() => onRemove(member.user_id)} leftIcon={<UserX className="w-3.5 h-3.5" />}>Remove</Button>
              </div>
            )}
          </div>
        ))}
      </CardContent>
    </Card>
  )
}
