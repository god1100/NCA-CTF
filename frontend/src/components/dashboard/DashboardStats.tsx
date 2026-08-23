import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../ui/Card'
import { Trophy, Flag, Users, Zap } from 'lucide-react'

export function DashboardStats() {
  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <Card className="border-l-4 border-l-nca-accent">
        <CardHeader className="pb-2"><CardTitle className="text-2xl font-bold">—</CardTitle><CardDescription>Total Score</CardDescription></CardHeader>
        <CardContent><div className="flex items-center gap-2 text-nca-text-muted text-sm"><Trophy className="w-4 h-4" /> Coming Soon</div></CardContent>
      </Card>
      <Card className="border-l-4 border-l-nca-success">
        <CardHeader className="pb-2"><CardTitle className="text-2xl font-bold">—</CardTitle><CardDescription>Challenges Solved</CardDescription></CardHeader>
        <CardContent><div className="flex items-center gap-2 text-nca-text-muted text-sm"><Flag className="w-4 h-4" /> Coming Soon</div></CardContent>
      </Card>
      <Card className="border-l-4 border-l-nca-warning">
        <CardHeader className="pb-2"><CardTitle className="text-2xl font-bold">—</CardTitle><CardDescription>Team Rank</CardDescription></CardHeader>
        <CardContent><div className="flex items-center gap-2 text-nca-text-muted text-sm"><Users className="w-4 h-4" /> Coming Soon</div></CardContent>
      </Card>
      <Card className="border-l-4 border-l-nca-info">
        <CardHeader className="pb-2"><CardTitle className="text-2xl font-bold">—</CardTitle><CardDescription>Available Challenges</CardDescription></CardHeader>
        <CardContent><div className="flex items-center gap-2 text-nca-text-muted text-sm"><Zap className="w-4 h-4" /> Coming Soon</div></CardContent>
      </Card>
    </div>
  )
}
