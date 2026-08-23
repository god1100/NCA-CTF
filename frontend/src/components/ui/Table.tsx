import { cn } from '../../utils/cn'
import type { ReactNode, ThHTMLAttributes, TdHTMLAttributes } from 'react'

export function Table({ children, className }: { children: ReactNode; className?: string }) {
  return <div className="overflow-x-auto"><table className={cn('w-full text-sm text-left', className)}>{children}</table></div>
}
export function TableHead({ children, className }: { children: ReactNode; className?: string }) {
  return <thead className={cn('text-xs text-nca-text-muted uppercase bg-nca-surface-hover', className)}>{children}</thead>
}
export function TableBody({ children, className }: { children: ReactNode; className?: string }) {
  return <tbody className={cn('divide-y divide-nca-border', className)}>{children}</tbody>
}
export function TableRow({ children, className }: { children: ReactNode; className?: string }) {
  return <tr className={cn('hover:bg-nca-surface-hover/50 transition-colors', className)}>{children}</tr>
}
export function TableHeader({ children, className, ...props }: ThHTMLAttributes<HTMLTableCellElement>) {
  return <th className={cn('px-4 py-3 font-medium text-nca-text-muted', className)} {...props}>{children}</th>
}
export function TableCell({ children, className, ...props }: TdHTMLAttributes<HTMLTableCellElement>) {
  return <td className={cn('px-4 py-3 text-nca-text', className)} {...props}>{children}</td>
}
