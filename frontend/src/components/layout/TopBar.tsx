import { Menu, Bell } from 'lucide-react'
import { useAuth } from '../../hooks/useAuth'

export function TopBar({ onMenuClick }: { onMenuClick: () => void }) {
  const { user } = useAuth()
  return (
    <header className="h-16 border-b border-nca-border bg-nca-surface/80 backdrop-blur-md flex items-center justify-between px-4 sm:px-6 lg:px-8 sticky top-0 z-30">
      <button onClick={onMenuClick} className="lg:hidden p-2 rounded-lg text-nca-text-muted hover:text-nca-text hover:bg-nca-surface-hover" aria-label="Open menu">
        <Menu className="w-5 h-5" />
      </button>
      <div className="flex-1" />
      <div className="flex items-center gap-4">
        <button className="p-2 rounded-lg text-nca-text-muted hover:text-nca-text hover:bg-nca-surface-hover relative" aria-label="Notifications">
          <Bell className="w-5 h-5" />
        </button>
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 rounded-full bg-nca-accent/10 border border-nca-accent/30 flex items-center justify-center">
            <span className="text-xs font-bold text-nca-accent">{user?.username?.charAt(0).toUpperCase()}</span>
          </div>
          <span className="hidden sm:block text-sm font-medium text-nca-text">{user?.username}</span>
        </div>
      </div>
    </header>
  )
}
