import { Table, TableHead, TableBody, TableRow, TableHeader, TableCell } from '../ui/Table'
import { Badge } from '../ui/Badge'
import { Trophy, Medal, Award } from 'lucide-react'
import type { LeaderboardEntry } from '../../types'

interface LeaderboardTableProps { entries: LeaderboardEntry[]; currentTeam?: string }

export function LeaderboardTable({ entries, currentTeam }: LeaderboardTableProps) {
  const getRankIcon = (rank: number) => {
    if (rank === 1) return <Trophy className="w-5 h-5 text-yellow-400" />
    if (rank === 2) return <Medal className="w-5 h-5 text-slate-300" />
    if (rank === 3) return <Award className="w-5 h-5 text-amber-600" />
    return <span className="text-sm text-nca-text-muted font-mono-code w-5 text-center">{rank}</span>
  }

  return (
    <div className="rounded-xl border border-nca-border overflow-hidden">
      <Table>
        <TableHead>
          <TableRow>
            <TableHeader>Rank</TableHeader>
            <TableHeader>Team</TableHeader>
            <TableHeader>Score</TableHeader>
            <TableHeader>Solved</TableHeader>
            <TableHeader className="hidden sm:table-cell">Last Activity</TableHeader>
          </TableRow>
        </TableHead>
        <TableBody>
          {entries.map(entry => (
            <TableRow key={entry.rank} className={entry.team === currentTeam ? 'bg-nca-accent/5' : ''}>
              <TableCell><div className="flex items-center gap-2">{getRankIcon(entry.rank)}</div></TableCell>
              <TableCell>
                <div className="flex items-center gap-2">
                  <span className="font-medium">{entry.team}</span>
                  {entry.team === currentTeam && <Badge variant="accent">You</Badge>}
                </div>
              </TableCell>
              <TableCell><span className="font-mono-code font-semibold">{entry.score.toLocaleString()}</span></TableCell>
              <TableCell><span className="font-mono-code">{entry.solved_count}</span></TableCell>
              <TableCell className="hidden sm:table-cell text-nca-text-muted">{new Date(entry.last_activity).toLocaleDateString()}</TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  )
}
