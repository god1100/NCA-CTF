import { useState, useEffect } from 'react'
import { challengeService } from '../../api/challenge.service'
import { Card, CardHeader, CardTitle, CardContent } from '../ui/Card'
import { Button } from '../ui/Button'
import { Spinner } from '../ui/Spinner'
import { EmptyState } from '../ui/EmptyState'
import { Alert } from '../ui/Alert'
import { Lightbulb, Eye, Lock } from 'lucide-react'
import type { ChallengeHint } from '../../types'

export function ChallengeHints({ challengeId }: { challengeId: number }) {
  const [hints, setHints] = useState<ChallengeHint[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [revealing, setRevealing] = useState<number | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    challengeService.hints(challengeId).then(res => setHints(res.hints)).finally(() => setIsLoading(false))
  }, [challengeId])

  const reveal = async (hintId: number) => {
    setRevealing(hintId); setError(null)
    try {
      const res = await challengeService.revealHint(hintId)
      setHints(prev => prev.map(h => h.id === hintId ? { ...h, ...res.hint } : h))
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to reveal hint')
    } finally {
      setRevealing(null)
    }
  }

  if (isLoading) return <Spinner size="sm" />
  if (hints.length === 0) return <EmptyState title="No hints" description="This challenge has no hints available." />

  return (
    <Card>
      <CardHeader><CardTitle className="text-base flex items-center gap-2"><Lightbulb className="w-4 h-4" /> Hints</CardTitle></CardHeader>
      <CardContent className="space-y-3">
        {error && <Alert variant="error">{error}</Alert>}
        {hints.map(hint => (
          <div key={hint.id} className="p-3 rounded-lg bg-nca-bg border border-nca-border">
            <div className="flex items-center justify-between gap-3">
              <div className="flex items-center gap-2">
                {hint.content ? <Eye className="w-4 h-4 text-nca-success" /> : <Lock className="w-4 h-4 text-nca-text-dim" />}
                <span className="text-sm font-medium text-nca-text">{hint.title || `Hint ${hint.id}`}</span>
              </div>
              {!hint.content && (
                <Button size="sm" variant="secondary" isLoading={revealing === hint.id} onClick={() => reveal(hint.id)}>
                  Reveal {hint.point_penalty > 0 && `(-${hint.point_penalty} pts)`}
                </Button>
              )}
            </div>
            {hint.content && (
              <div className="mt-2 p-2 rounded bg-nca-surface-hover">
                <p className="text-sm text-nca-text-muted">{hint.content}</p>
              </div>
            )}
          </div>
        ))}
      </CardContent>
    </Card>
  )
}
