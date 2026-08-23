import { Link, useLocation } from 'react-router-dom'
import { useAuth } from '../../hooks/useAuth'
import { LayoutDashboard, Users, Flag, BarChart3, UserCircle, LogOut, Shield, X } from 'lucide-react'
import { cn } from '../../utils/cn'

export function MobileDrawer({ isOpen, onClose }: { isOpen: boolean; onClose: () => void }) {
  const { user, logout } = useAuth()
  const location = useLocation()
  const isActive = (path: string) => location.pathname === path || location.pathname.startsWith(path + '/')

  const navItems = [
    { to: '/dashboard', label: 'Dashboard', icon: LayoutDashboard },
    { to: '/challenges', label: 'Challenges', icon: Flag },
    { to: '/leaderboard', label: 'Leaderboard', icon: BarChart3 },
    { to: '/team', label: 'Team', icon: Users },
    { to: '/profile', label: 'Profile', icon: UserCircle },
  ]

  if (!isOpen) return null

  return (
    <div className="lg:hidden fixed inset-0 z-50">
      <div className="absolute inset-0 bg-black/60 backdrop-blur-sm" onClick={onClose} />
      <div className="absolute left-0 top-0 h-full w-72 bg-nca-surface border-r border-nca-border flex flex-col">
        <div className="flex items-center justify-between px-4 h-16 border-b border-nca-border">
          <div className="flex items-center gap-2.5">
            <div className="w-8 h-8 rounded-lg bg-nca-accent/10 border border-nca-accent/30 flex items-center justify-center">
              <Shield className="w-4.5 h-4.5 text-nca-accent" />
            </div>
            <span className="font-bold text-nca-text">NCA-CTF</span>
          </div>
          <button onClick={onClose} className="p-2 rounded-lg text-nca-text-muted hover:text-nca-text hover:bg-nca-surface-hover" aria-label="Close menu">
            <X className="w-5 h-5" />
          </button>
        </div>
        <nav className="flex-1 px-3 py-4 space-y-1">
          {navItems.map(item => (
            <Link key={item.to} to={item.to} onClick={onClose} className={cn('flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors', isActive(item.to) ? 'text-nca-accent bg-nca-accent/10' : 'text-nca-text-muted hover:text-nca-text hover:bg-nca-surface-hover')}>
              <item.icon className="w-5 h-5" />
              {item.label}
            </Link>
          ))}
        </nav>
        <div className="px-3 py-4 border-t border-nca-border">
          <div className="px-3 py-2 mb-2">
            <p className="text-xs text-nca-text-dim">Signed in as</p>
            <p className="text-sm font-medium text-nca-text truncate">{user?.username}</p>
          </div>
          <button onClick={() => { logout(); onClose(); }} className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-nca-text-muted hover:text-nca-error hover:bg-nca-error/10 transition-colors w-full">
            <LogOut className="w-5 h-5" />
            Sign Out
          </button>
        </div>
      </div>
    </div>
  )
}
