import { useState } from 'react'
import { useFlagSubmission } from '../../hooks/useFlagSubmission'
import { Card, CardHeader, CardTitle, CardContent } from '../ui/Card'
import { Input } from '../ui/Input'
import { Button } from '../ui/Button'
import { Alert } from '../ui/Alert'
import { Flag, CheckCircle, XCircle, AlertTriangle, RotateCcw } from 'lucide-react'

interface FlagSubmissionProps { challengeId: number }

export function FlagSubmission({ challengeId }: FlagSubmissionProps) {
  const [flag, setFlag] = useState('')
  const { result, status, isSubmitting, submit, reset } = useFlagSubmission()

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!flag.trim()) return
    await submit(challengeId, flag.trim())
  }

  const statusConfig = {
    idle: null,
    submitting: { icon: <Flag className="w-5 h-5 text-nca-accent animate-pulse" />, title: 'Submitting...', variant: 'info' as const },
    correct: { icon: <CheckCircle className="w-5 h-5 text-nca-success" />, title: 'Correct!', variant: 'success' as const },
    incorrect: { icon: <XCircle className="w-5 h-5 text-nca-error" />, title: 'Incorrect', variant: 'error' as const },
    already_solved: { icon: <CheckCircle className="w-5 h-5 text-nca-warning" />, title: 'Already Solved', variant: 'warning' as const },
    rate_limited: { icon: <AlertTriangle className="w-5 h-5 text-nca-warning" />, title: 'Rate Limited', variant: 'warning' as const },
    error: { icon: <XCircle className="w-5 h-5 text-nca-error" />, title: 'Error', variant: 'error' as const },
  }

  const currentStatus = statusConfig[status]

  return (
    <Card className="border-l-4 border-l-nca-accent">
      <CardHeader><CardTitle className="text-base flex items-center gap-2"><Flag className="w-4 h-4" /> Submit Flag</CardTitle></CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-3">
          <div className="flex gap-2">
            <Input
              value={flag}
              onChange={e => setFlag(e.target.value)}
              placeholder="flag{...}"
              className="font-mono-code"
              disabled={isSubmitting || status === 'correct'}
            />
            {status === 'correct' ? (
              <Button type="button" variant="secondary" onClick={reset} leftIcon={<RotateCcw className="w-4 h-4" />}>Reset</Button>
            ) : (
              <Button type="submit" isLoading={isSubmitting} disabled={!flag.trim()}>Submit</Button>
            )}
          </div>
          {currentStatus && status !== 'idle' && (
            <Alert variant={currentStatus.variant} title={currentStatus.title}>
              <div className="flex items-center gap-2">
                {currentStatus.icon}
                <span>{result?.message}</span>
              </div>
              {result?.points !== undefined && (
                <p className="mt-1 text-sm font-medium">+{result.points} points {result.firstBlood && <span className="text-nca-warning">(First Blood!)</span>}</p>
              )}
            </Alert>
          )}
        </form>
      </CardContent>
    </Card>
  )
}
