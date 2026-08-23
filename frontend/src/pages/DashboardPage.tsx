import { useAuth } from '../hooks/useAuth'
import { DashboardStats } from '../components/dashboard/DashboardStats'
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '../components/ui/Card'
import { Link } from 'react-router-dom'
import { Flag, Users, Trophy, ArrowRight } from 'lucide-react'

export function DashboardPage() {
  const { user } = useAuth()

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-nca-text">Dashboard</h1>
        <p className="text-sm text-nca-text-muted mt-1">Welcome back, {user?.username || 'player'}.</p>
      </div>

      <DashboardStats />

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle className="text-base flex items-center gap-2"><Flag className="w-4 h-4 text-nca-accent" /> Quick Actions</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2">
            <Link to="/challenges" className="flex items-center justify-between p-3 rounded-lg bg-nca-bg border border-nca-border hover:border-nca-accent/30 transition-colors">
              <div className="flex items-center gap-3">
                <Flag className="w-4 h-4 text-nca-accent" />
                <span className="text-sm font-medium text-nca-text">Browse Challenges</span>
              </div>
              <ArrowRight className="w-4 h-4 text-nca-text-dim" />
            </Link>
            <Link to="/team" className="flex items-center justify-between p-3 rounded-lg bg-nca-bg border border-nca-border hover:border-nca-accent/30 transition-colors">
              <div className="flex items-center gap-3">
                <Users className="w-4 h-4 text-nca-accent" />
                <span className="text-sm font-medium text-nca-text">Manage Team</span>
              </div>
              <ArrowRight className="w-4 h-4 text-nca-text-dim" />
            </Link>
            <Link to="/leaderboard" className="flex items-center justify-between p-3 rounded-lg bg-nca-bg border border-nca-border hover:border-nca-accent/30 transition-colors">
              <div className="flex items-center gap-3">
                <Trophy className="w-4 h-4 text-nca-accent" />
                <span className="text-sm font-medium text-nca-text">View Leaderboard</span>
              </div>
              <ArrowRight className="w-4 h-4 text-nca-text-dim" />
            </Link>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="text-base">Recent Activity</CardTitle>
            <CardDescription>Your latest challenge activity will appear here.</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="text-center py-8">
              <p className="text-sm text-nca-text-muted">No recent activity.</p>
              <p className="text-xs text-nca-text-dim mt-1">Activity tracking coming with Phase 5.</p>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
