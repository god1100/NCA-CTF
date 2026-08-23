import { forwardRef, type SelectHTMLAttributes } from 'react'
import { cn } from '../../utils/cn'
import { ChevronDown } from 'lucide-react'

interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
  label?: string
  error?: string
  options: { value: string; label: string }[]
}

export const Select = forwardRef<HTMLSelectElement, SelectProps>(({ label, error, options, className, ...props }, ref) => (
  <div className="w-full">
    {label && <label className="block text-sm font-medium text-nca-text-muted mb-1.5">{label}</label>}
    <div className="relative">
      <select ref={ref} className={cn('w-full px-3.5 py-2.5 pr-10 rounded-lg bg-nca-surface border text-sm text-nca-text appearance-none transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-nca-accent/40 focus:border-nca-accent/50', error ? 'border-nca-error/50 focus:ring-nca-error/40 focus:border-nca-error/50' : 'border-nca-border hover:border-nca-border-subtle', className)} {...props}>
        {options.map(opt => <option key={opt.value} value={opt.value}>{opt.label}</option>)}
      </select>
      <ChevronDown className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-nca-text-dim pointer-events-none" />
    </div>
    {error && <p className="mt-1.5 text-xs text-nca-error" role="alert">{error}</p>}
  </div>
))
Select.displayName = 'Select'
