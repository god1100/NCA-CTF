import { api } from './client'
import type { Team, TeamMember, MyTeamResponse, TeamInvitation } from '../types'

export const teamService = {
  create: (name: string) =>
    api.post<{ team: Team }>('/teams', { name }),

  me: () =>
    api.get<MyTeamResponse>('/teams/me'),

  members: () =>
    api.get<{ members: TeamMember[] }>('/teams/me/members'),

  removeMember: (userId: number) =>
    api.del<{}>(`/teams/me/members/${userId}`),

  leave: () =>
    api.post<{}>('/teams/me/leave'),

  transferCaptain: (userId: number) =>
    api.post<{}>('/teams/me/transfer-captain', { user_id: userId }),

  invitations: () =>
    api.get<{ invitations: TeamInvitation[] }>('/teams/me/invitations'),

  createInvitation: (email: string) =>
    api.post<{ invitation: TeamInvitation; token: string }>('/teams/me/invitations', { email }),

  acceptInvitation: (token: string) =>
    api.post<{}>(`/team-invitations/${token}/accept`),

  rejectInvitation: (token: string) =>
    api.post<{}>(`/team-invitations/${token}/reject`),
}
