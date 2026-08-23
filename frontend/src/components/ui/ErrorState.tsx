import { cn } from '../../utils/cn'
import type { ReactNode } from 'react'
import { AlertTriangle } from 'lucide-react'
import { Button } from './Button'

interface ErrorStateProps { title?: string; description?: string; onRetry?: () => void; action?: ReactNode; className?: string }

export function ErrorState({ title = 'Something went wrong', description = 'We could not load the requested information.', onRetry, action, className }: ErrorStateProps) {
  return (
    <div className={cn('flex flex-col items-center justify-center py-12 text-center', className)}>
      <div className="w-12 h-12 rounded-xl bg-nca-error/10 flex items-center justify-center mb-4">
        <AlertTriangle className="w-6 h-6 text-nca-error" />
      </div>
      <h3 className="text-sm font-medium text-nca-text">{title}</h3>
      <p className="text-sm text-nca-text-muted mt-1 max-w-sm">{description}</p>
      {(onRetry || action) && (
        <div className="mt-4 flex gap-3">
          {onRetry && <Button onClick={onRetry} variant="secondary">Try Again</Button>}
          {action}
        </div>
      )}
    </div>
  )
}
