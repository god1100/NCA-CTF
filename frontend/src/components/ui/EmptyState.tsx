import { cn } from '../../utils/cn'
import type { ReactNode } from 'react'
import { Inbox } from 'lucide-react'

interface EmptyStateProps { title?: string; description?: string; icon?: ReactNode; action?: ReactNode; className?: string }

export function EmptyState({ title = 'Nothing here', description = 'There are no items to display.', icon, action, className }: EmptyStateProps) {
  return (
    <div className={cn('flex flex-col items-center justify-center py-12 text-center', className)}>
      <div className="w-12 h-12 rounded-xl bg-nca-surface-hover flex items-center justify-center mb-4">
        {icon || <Inbox className="w-6 h-6 text-nca-text-dim" />}
      </div>
      <h3 className="text-sm font-medium text-nca-text">{title}</h3>
      <p className="text-sm text-nca-text-muted mt-1 max-w-sm">{description}</p>
      {action && <div className="mt-4">{action}</div>}
    </div>
  )
}
