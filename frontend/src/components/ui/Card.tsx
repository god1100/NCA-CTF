import { cn } from '../../utils/cn'
import type { HTMLAttributes, ReactNode } from 'react'

export function Card({ children, className, ...props }: HTMLAttributes<HTMLDivElement> & { children: ReactNode }) {
  return <div className={cn('rounded-xl border border-nca-border bg-nca-surface p-5 transition-all duration-200', className)} {...props}>{children}</div>
}
export function CardHeader({ children, className }: { children: ReactNode; className?: string }) {
  return <div className={cn('mb-4', className)}>{children}</div>
}
export function CardTitle({ children, className }: { children: ReactNode; className?: string }) {
  return <h3 className={cn('text-lg font-semibold text-nca-text', className)}>{children}</h3>
}
export function CardDescription({ children, className }: { children: ReactNode; className?: string }) {
  return <p className={cn('text-sm text-nca-text-muted mt-1', className)}>{children}</p>
}
export function CardContent({ children, className }: { children: ReactNode; className?: string }) {
  return <div className={className}>{children}</div>
}
export function CardFooter({ children, className }: { children: ReactNode; className?: string }) {
  return <div className={cn('mt-4 pt-4 border-t border-nca-border', className)}>{children}</div>
}
