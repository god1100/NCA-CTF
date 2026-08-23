import { forwardRef, type TextareaHTMLAttributes } from 'react'
import { cn } from '../../utils/cn'

interface TextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
  label?: string
  error?: string
}

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(({ label, error, className, ...props }, ref) => (
  <div className="w-full">
    {label && <label className="block text-sm font-medium text-nca-text-muted mb-1.5">{label}</label>}
    <textarea ref={ref} className={cn('w-full px-3.5 py-2.5 rounded-lg bg-nca-surface border text-sm text-nca-text placeholder:text-nca-text-dim transition-all duration-200 resize-y min-h-[100px] focus:outline-none focus:ring-2 focus:ring-nca-accent/40 focus:border-nca-accent/50', error ? 'border-nca-error/50 focus:ring-nca-error/40 focus:border-nca-error/50' : 'border-nca-border hover:border-nca-border-subtle', className)} {...props} />
    {error && <p className="mt-1.5 text-xs text-nca-error" role="alert">{error}</p>}
  </div>
))
Textarea.displayName = 'Textarea'
