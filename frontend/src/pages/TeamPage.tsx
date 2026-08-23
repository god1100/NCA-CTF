import { useState } from 'react'
import { useTeam } from '../hooks/useTeam'
import { TeamInfo } from '../components/team/TeamInfo'
import { TeamMemberList } from '../components/team/TeamMemberList'
import { TeamInvitations } from '../components/team/TeamInvitations'
import { TeamActions } from '../components/team/TeamActions'
import { Card, CardHeader, CardTitle, CardContent } from '../components/ui/Card'
import { Button } from '../components/ui/Button'
import { Input } from '../components/ui/Input'
import { Spinner } from '../components/ui/Spinner'
import { ErrorState } from '../components/ui/ErrorState'
import { Alert } from '../components/ui/Alert'
import { Users, Plus, LogIn } from 'lucide-react'

export function TeamPage() {
  const {
    teamInfo, members, invitations, isLoading, error, refetch,
    createTeam, leaveTeam, removeMember, transferCaptain, createInvitation,
    acceptInvitation,
  } = useTeam()

  const [newTeamName, setNewTeamName] = useState('')
  const [isCreating, setIsCreating] = useState(false)
  const [createError, setCreateError] = useState<string | null>(null)

  const [inviteToken, setInviteToken] = useState('')
  const [isAccepting, setIsAccepting] = useState(false)
  const [acceptError, setAcceptError] = useState<string | null>(null)
  const [acceptSuccess, setAcceptSuccess] = useState<string | null>(null)

  const handleCreate = async () => {
    if (!newTeamName.trim()) return
    setIsCreating(true); setCreateError(null)
    try {
      await createTeam(newTeamName.trim())
      setNewTeamName('')
    } catch (err) {
      setCreateError(err instanceof Error ? err.message : 'Failed to create team')
    } finally {
      setIsCreating(false)
    }
  }

  const handleAcceptToken = async () => {
    if (!inviteToken.trim()) return
    setIsAccepting(true); setAcceptError(null); setAcceptSuccess(null)
    try {
      await acceptInvitation(inviteToken.trim())
      setAcceptSuccess('Invitation accepted! You have joined the team.')
      setInviteToken('')
    } catch (err) {
      setAcceptError(err instanceof Error ? err.message : 'Failed to accept invitation')
    } finally {
      setIsAccepting(false)
    }
  }

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Spinner />
      </div>
    )
  }

  if (error) {
    return <ErrorState title="Failed to load team" description={error} onRetry={refetch} />
  }

  if (!teamInfo?.team) {
    return (
      <div className="max-w-md mx-auto space-y-6">
        <div className="text-center">
          <div className="w-12 h-12 rounded-xl bg-nca-accent/10 flex items-center justify-center mx-auto mb-4">
            <Users className="w-6 h-6 text-nca-accent" />
          </div>
          <h1 className="text-xl font-bold text-nca-text">No Team</h1>
          <p className="text-sm text-nca-text-muted mt-1">You are not currently a member of any team.</p>
        </div>

        <Card>
          <CardHeader><CardTitle className="text-base">Create a Team</CardTitle></CardHeader>
          <CardContent className="space-y-3">
            {createError && <Alert variant="error">{createError}</Alert>}
            <Input value={newTeamName} onChange={e => setNewTeamName(e.target.value)} placeholder="Team name" />
            <Button onClick={handleCreate} isLoading={isCreating} className="w-full" leftIcon={<Plus className="w-4 h-4" />}>Create Team</Button>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle className="text-base">Join a Team</CardTitle></CardHeader>
          <CardContent className="space-y-3">
            {acceptError && <Alert variant="error">{acceptError}</Alert>}
            {acceptSuccess && <Alert variant="success">{acceptSuccess}</Alert>}
            <p className="text-sm text-nca-text-muted">Enter an invitation token to join an existing team.</p>
            <Input value={inviteToken} onChange={e => setInviteToken(e.target.value)} placeholder="Invitation token" />
            <Button onClick={handleAcceptToken} isLoading={isAccepting} className="w-full" variant="secondary" leftIcon={<LogIn className="w-4 h-4" />}>Join Team</Button>
          </CardContent>
        </Card>
      </div>
    )
  }

  return (
    <div className="space-y-6 max-w-4xl">
      <div>
        <h1 className="text-2xl font-bold text-nca-text">Team</h1>
        <p className="text-sm text-nca-text-muted mt-1">Manage your team and members.</p>
      </div>

      <TeamInfo team={teamInfo.team} isCaptain={teamInfo.is_captain} />
      <TeamMemberList members={members} isCaptain={teamInfo.is_captain} onRemove={removeMember} onTransfer={transferCaptain} />
      <TeamInvitations invitations={invitations} isCaptain={teamInfo.is_captain} onCreate={createInvitation} />
      <TeamActions isCaptain={teamInfo.is_captain} onLeave={leaveTeam} />
    </div>
  )
}
