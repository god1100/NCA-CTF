import { cn } from '../../utils/cn'
import type { ReactNode } from 'react'
import { AlertTriangle, CheckCircle, Info, XCircle } from 'lucide-react'

interface AlertProps { children: ReactNode; variant?: 'info' | 'success' | 'warning' | 'error'; title?: string; className?: string }

export function Alert({ children, variant = 'info', title, className }: AlertProps) {
  const icons = { info: Info, success: CheckCircle, warning: AlertTriangle, error: XCircle }
  const variants = { info: 'bg-nca-info/10 text-nca-info border-nca-info/30', success: 'bg-nca-success/10 text-nca-success border-nca-success/30', warning: 'bg-nca-warning/10 text-nca-warning border-nca-warning/30', error: 'bg-nca-error/10 text-nca-error border-nca-error/30' }
  const Icon = icons[variant]
  return (
    <div className={cn('rounded-lg border p-4 flex gap-3', variants[variant], className)} role="alert">
      <Icon className="w-5 h-5 shrink-0 mt-0.5" />
      <div>
        {title && <p className="font-medium text-sm">{title}</p>}
        <div className="text-sm opacity-90">{children}</div>
      </div>
    </div>
  )
}
