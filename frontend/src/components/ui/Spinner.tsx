import { cn } from '../../utils/cn'

interface SpinnerProps { size?: 'sm' | 'md' | 'lg'; className?: string }

export function Spinner({ size = 'md', className }: SpinnerProps) {
  const sizes = { sm: 'w-4 h-4', md: 'w-8 h-8', lg: 'w-12 h-12' }
  return (
    <div className={cn('flex items-center justify-center', className)} role="status" aria-label="Loading">
      <div className={cn('border-2 border-nca-accent border-t-transparent rounded-full animate-spin', sizes[size])} />
      <span className="sr-only">Loading...</span>
    </div>
  )
}
