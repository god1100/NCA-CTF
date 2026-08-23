import { cn } from '../../utils/cn'
import type { ButtonHTMLAttributes, ReactNode } from 'react'

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'secondary' | 'ghost' | 'danger'
  size?: 'sm' | 'md' | 'lg'
  isLoading?: boolean
  leftIcon?: ReactNode
  rightIcon?: ReactNode
}

export function Button({ variant = 'primary', size = 'md', isLoading = false, leftIcon, rightIcon, children, className, disabled, ...props }: ButtonProps) {
  const variants = {
    primary: 'bg-nca-accent/10 text-nca-accent border border-nca-accent/30 hover:bg-nca-accent/20 hover:border-nca-accent/50',
    secondary: 'bg-nca-surface-hover text-nca-text border border-nca-border hover:border-nca-border-subtle',
    ghost: 'bg-transparent text-nca-text-muted hover:text-nca-text hover:bg-nca-surface-hover',
    danger: 'bg-nca-error/10 text-nca-error border border-nca-error/30 hover:bg-nca-error/20',
  }
  const sizes = { sm: 'px-3 py-1.5 text-xs', md: 'px-4 py-2 text-sm', lg: 'px-6 py-3 text-base' }
  return (
    <button className={cn('inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-nca-accent/50 focus:ring-offset-2 focus:ring-offset-nca-bg disabled:opacity-50 disabled:cursor-not-allowed', variants[variant], sizes[size], className)} disabled={disabled || isLoading} {...props}>
      {isLoading && <span className="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin" />}
      {!isLoading && leftIcon}
      {children}
      {!isLoading && rightIcon}
    </button>
  )
}
