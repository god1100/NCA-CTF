import { useState, useEffect, useCallback } from 'react'
import { teamService } from '../api/team.service'
import type { TeamMember, MyTeamResponse, TeamInvitation } from '../types'

export function useTeam() {
  const [teamInfo, setTeamInfo] = useState<MyTeamResponse | null>(null)
  const [members, setMembers] = useState<TeamMember[]>([])
  const [invitations, setInvitations] = useState<TeamInvitation[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const refetch = useCallback(async () => {
    setIsLoading(true); setError(null)
    try {
      const [me, mems, invs] = await Promise.all([
        teamService.me(),
        teamService.members().catch(() => ({ members: [] })),
        teamService.invitations().catch(() => ({ invitations: [] })),
      ])
      setTeamInfo(me)
      setMembers(mems.members)
      setInvitations(invs.invitations)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load team')
    } finally {
      setIsLoading(false)
    }
  }, [])

  useEffect(() => { refetch() }, [refetch])

  const createTeam = async (name: string) => { await teamService.create(name); await refetch() }
  const leaveTeam = async () => { await teamService.leave(); await refetch() }
  const removeMember = async (userId: number) => { await teamService.removeMember(userId); await refetch() }
  const transferCaptain = async (userId: number) => { await teamService.transferCaptain(userId); await refetch() }
  const createInvitation = async (email: string) => {
    const res = await teamService.createInvitation(email)
    await refetch()
    return { token: res.token }
  }
  const acceptInvitation = async (token: string) => { await teamService.acceptInvitation(token); await refetch() }
  const rejectInvitation = async (token: string) => { await teamService.rejectInvitation(token); await refetch() }

  return {
    teamInfo, members, invitations, isLoading, error, refetch,
    createTeam, leaveTeam, removeMember, transferCaptain,
    createInvitation, acceptInvitation, rejectInvitation,
  }
}
