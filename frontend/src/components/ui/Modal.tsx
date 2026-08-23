import { useEffect, type ReactNode } from 'react'
import { cn } from '../../utils/cn'
import { X } from 'lucide-react'

interface ModalProps { isOpen: boolean; onClose: () => void; title?: string; children: ReactNode; className?: string }

export function Modal({ isOpen, onClose, title, children, className }: ModalProps) {
  useEffect(() => {
    if (isOpen) document.body.style.overflow = 'hidden'
    else document.body.style.overflow = ''
    return () => { document.body.style.overflow = '' }
  }, [isOpen])
  if (!isOpen) return null
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby={title ? 'modal-title' : undefined}>
      <div className="absolute inset-0 bg-black/60 backdrop-blur-sm" onClick={onClose} />
      <div className={cn('relative bg-nca-surface border border-nca-border rounded-xl shadow-xl w-full max-w-lg', className)}>
        {title && (
          <div className="flex items-center justify-between px-6 py-4 border-b border-nca-border">
            <h2 id="modal-title" className="text-lg font-semibold text-nca-text">{title}</h2>
            <button onClick={onClose} className="p-1 rounded-lg text-nca-text-muted hover:text-nca-text hover:bg-nca-surface-hover transition-colors" aria-label="Close dialog"><X className="w-5 h-5" /></button>
          </div>
        )}
        <div className="p-6">{children}</div>
      </div>
    </div>
  )
}
