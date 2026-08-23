import { cn } from '../../utils/cn'
import { ChevronLeft, ChevronRight } from 'lucide-react'
import { Button } from './Button'

interface PaginationProps { page: number; totalPages: number; onPageChange: (page: number) => void; className?: string }

export function Pagination({ page, totalPages, onPageChange, className }: PaginationProps) {
  if (totalPages <= 1) return null
  const pages = Array.from({ length: Math.min(5, totalPages) }, (_, i) => {
    if (totalPages <= 5) return i + 1
    if (page <= 3) return i + 1
    if (page >= totalPages - 2) return totalPages - 4 + i
    return page - 2 + i
  })
  return (
    <div className={cn('flex items-center justify-center gap-2', className)}>
      <Button variant="ghost" size="sm" onClick={() => onPageChange(page - 1)} disabled={page <= 1} leftIcon={<ChevronLeft className="w-4 h-4" />}>Prev</Button>
      {pages.map(p => (
        <button key={p} onClick={() => onPageChange(p)} className={cn('w-8 h-8 rounded-lg text-sm font-medium transition-colors', p === page ? 'bg-nca-accent/10 text-nca-accent border border-nca-accent/30' : 'text-nca-text-muted hover:text-nca-text hover:bg-nca-surface-hover')}>
          {p}
        </button>
      ))}
      <Button variant="ghost" size="sm" onClick={() => onPageChange(page + 1)} disabled={page >= totalPages} rightIcon={<ChevronRight className="w-4 h-4" />}>Next</Button>
    </div>
  )
}
