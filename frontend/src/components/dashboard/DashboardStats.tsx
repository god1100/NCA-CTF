import { useState, useEffect } from 'react'
import { useAuth } from '../../hooks/useAuth'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../ui/Card'
import { teamService } from '../../api/team.service'
import { challengeService } from '../../api/challenge.service'
import { Skeleton } from '../ui/Skeleton'
import { Users, Flag, Layers, Shield } from 'lucide-react'

export function DashboardStats() {
  const { user } = useAuth()
  const [teamName, setTeamName] = useState<string | null>(null)
  const [memberCount, setMemberCount] = useState<number | null>(null)
  const [categoryCount, setCategoryCount] = useState<number | null>(null)
  const [challengeCount, setChallengeCount] = useState<number | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    let cancelled = false
    async function load() {
      try {
        const [teamRes, catsRes, challengesRes] = await Promise.all([
          teamService.me().catch(() => null),
          challengeService.categories(),
          challengeService.list({ page: 1, per_page: 1 }),
        ])
        if (cancelled) return
        setTeamName(teamRes?.team?.name ?? null)
        setCategoryCount(catsRes.categories.length)
        setChallengeCount(challengesRes.pagination.total)
      } catch {
        // silently fail — dashboard should not crash on stats
      } finally {
        if (!cancelled) setIsLoading(false)
      }
    }
    load()
    return () => { cancelled = true }
  }, [])

  useEffect(() => {
    if (!teamName) return
    let cancelled = false
    teamService.members().then(res => {
      if (!cancelled) setMemberCount(res.members.length)
    }).catch(() => {
      if (!cancelled) setMemberCount(null)
    })
    return () => { cancelled = true }
  }, [teamName])

  const StatCard = ({
    icon: Icon,
    label,
    value,
    subtext,
  }: {
    icon: typeof Users
    label: string
    value: React.ReactNode
    subtext?: string
  }) => (
    <Card className="border-l-4 border-l-nca-accent">
      <CardHeader className="pb-2">
        <CardTitle className="text-2xl font-bold">{value}</CardTitle>
        <CardDescription>{label}</CardDescription>
      </CardHeader>
      <CardContent>
        <div className="flex items-center gap-2 text-nca-text-muted text-sm">
          <Icon className="w-4 h-4" />
          <span>{subtext ?? '—'}</span>
        </div>
      </CardContent>
    </Card>
  )

  if (isLoading) {
    return (
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {Array.from({ length: 4 }).map((_, i) => (
          <Card key={i} className="border-l-4 border-l-nca-accent">
            <CardHeader className="pb-2"><Skeleton className="h-8 w-16" /><Skeleton className="h-4 w-24 mt-2" /></CardHeader>
            <CardContent><Skeleton className="h-4 w-32" /></CardContent>
          </Card>
        ))}
      </div>
    )
  }

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <StatCard
        icon={Shield}
        label="Account Status"
        value={
          <span className={user?.status === 'active' ? 'text-nca-success' : 'text-nca-text-muted'}>
            {user?.status ?? '—'}
          </span>
        }
        subtext={user?.role ? `Role: ${user.role}` : 'Participant'}
      />
      <StatCard
        icon={Users}
        label="Team"
        value={teamName ?? 'No Team'}
        subtext={memberCount !== null ? `${memberCount} member${memberCount === 1 ? '' : 's'}` : '—'}
      />
      <StatCard
        icon={Layers}
        label="Categories"
        value={categoryCount ?? '—'}
        subtext="Challenge categories"
      />
      <StatCard
        icon={Flag}
        label="Challenges"
        value={challengeCount ?? '—'}
        subtext="Total available"
      />
    </div>
  )
}
