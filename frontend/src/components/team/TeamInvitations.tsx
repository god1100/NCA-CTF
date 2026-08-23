import { useState } from 'react'
import { Card, CardHeader, CardTitle, CardContent } from '../ui/Card'
import { Input } from '../ui/Input'
import { Button } from '../ui/Button'
import { Alert } from '../ui/Alert'
import { Copy, Check, Mail, Clock } from 'lucide-react'
import type { TeamInvitation } from '../../types'

interface TeamInvitationsProps {
  invitations: TeamInvitation[]
  isCaptain: boolean
  onCreate: (email: string) => Promise<{ token: string } | null>
}

export function TeamInvitations({ invitations, isCaptain, onCreate }: TeamInvitationsProps) {
  const [email, setEmail] = useState('')
  const [isLoading, setIsLoading] = useState(false)
  const [newToken, setNewToken] = useState<string | null>(null)
  const [copied, setCopied] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const handleCreate = async () => {
    if (!email.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      setError('Please enter a valid email.')
      return
    }
    setIsLoading(true); setError(null)
    try {
      const res = await onCreate(email.trim())
      if (res) { setNewToken(res.token); setEmail('') }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to create invitation')
    } finally {
      setIsLoading(false)
    }
  }

  const copyToken = () => {
    if (newToken) {
      navigator.clipboard.writeText(newToken)
      setCopied(true)
      setTimeout(() => setCopied(false), 2000)
    }
  }

  return (
    <Card>
      <CardHeader><CardTitle className="text-base flex items-center gap-2"><Mail className="w-4 h-4" /> Invitations</CardTitle></CardHeader>
      <CardContent className="space-y-4">
        {isCaptain && (
          <>
            <div className="flex gap-2">
              <Input type="email" placeholder="Invite by email" value={email} onChange={e => setEmail(e.target.value)} />
              <Button onClick={handleCreate} isLoading={isLoading}>Invite</Button>
            </div>
            {error && <Alert variant="error">{error}</Alert>}
            {newToken && (
              <Alert variant="warning" title="Invitation Token Created">
                <div className="space-y-2">
                  <p className="text-sm">Share this token with the invitee. It will not be shown again.</p>
                  <div className="flex items-center gap-2 p-2 rounded bg-nca-bg border border-nca-border">
                    <code className="text-sm font-mono-code text-nca-accent flex-1 break-all">{newToken}</code>
                    <Button size="sm" variant="secondary" onClick={copyToken} leftIcon={copied ? <Check className="w-3.5 h-3.5" /> : <Copy className="w-3.5 h-3.5" />}>
                      {copied ? 'Copied' : 'Copy'}
                    </Button>
                  </div>
                </div>
              </Alert>
            )}
          </>
        )}
        {invitations.length > 0 ? (
          <div className="space-y-2">
            {invitations.map(inv => (
              <div key={inv.id} className="flex items-center justify-between p-3 rounded-lg bg-nca-bg border border-nca-border">
                <div className="flex items-center gap-2">
                  <Clock className="w-4 h-4 text-nca-text-dim" />
                  <span className="text-sm text-nca-text">{inv.email}</span>
                  <span className="text-xs text-nca-text-dim capitalize">({inv.status})</span>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <p className="text-sm text-nca-text-muted text-center py-4">No pending invitations.</p>
        )}
      </CardContent>
    </Card>
  )
}
