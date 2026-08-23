import { cn } from '../../utils/cn'
import type { ReactNode } from 'react'

interface BadgeProps { children: ReactNode; variant?: 'default' | 'success' | 'warning' | 'error' | 'info' | 'accent'; className?: string }

export function Badge({ children, variant = 'default', className }: BadgeProps) {
  const variants = {
    default: 'bg-nca-surface-hover text-nca-text-muted border-nca-border',
    success: 'bg-nca-success/10 text-nca-success border-nca-success/30',
    warning: 'bg-nca-warning/10 text-nca-warning border-nca-warning/30',
    error: 'bg-nca-error/10 text-nca-error border-nca-error/30',
    info: 'bg-nca-info/10 text-nca-info border-nca-info/30',
    accent: 'bg-nca-accent/10 text-nca-accent border-nca-accent/30',
  }
  return <span className={cn('inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium border', variants[variant], className)}>{children}</span>
}
