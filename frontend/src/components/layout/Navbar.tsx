import { Link, useLocation } from 'react-router-dom'
import { useAuth } from '../../hooks/useAuth'
import { Shield, Menu, X } from 'lucide-react'
import { useState } from 'react'
import { cn } from '../../utils/cn'
import { Button } from '../ui/Button'

export function Navbar() {
  const { isAuthenticated } = useAuth()
  const location = useLocation()
  const [mobileOpen, setMobileOpen] = useState(false)
  const isActive = (path: string) => location.pathname === path
  const navLinks = isAuthenticated
    ? [{ to: '/dashboard', label: 'Dashboard' }, { to: '/challenges', label: 'Challenges' }, { to: '/leaderboard', label: 'Leaderboard' }, { to: '/team', label: 'Team' }]
    : [{ to: '/', label: 'Home' }]

  return (
    <nav className="sticky top-0 z-40 border-b border-nca-border bg-nca-bg/80 backdrop-blur-md">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-16">
          <Link to={isAuthenticated ? '/dashboard' : '/'} className="flex items-center gap-2.5">
            <div className="w-8 h-8 rounded-lg bg-nca-accent/10 border border-nca-accent/30 flex items-center justify-center">
              <Shield className="w-4.5 h-4.5 text-nca-accent" />
            </div>
            <span className="font-bold text-nca-text tracking-tight">NCA-CTF</span>
          </Link>
          <div className="hidden md:flex items-center gap-1">
            {navLinks.map(link => (
              <Link key={link.to} to={link.to} className={cn('px-3 py-2 rounded-lg text-sm font-medium transition-colors', isActive(link.to) ? 'text-nca-accent bg-nca-accent/10' : 'text-nca-text-muted hover:text-nca-text hover:bg-nca-surface-hover')}>
                {link.label}
              </Link>
            ))}
          </div>
          <div className="hidden md:flex items-center gap-3">
            {isAuthenticated ? (
              <Link to="/profile" className="text-sm text-nca-text-muted hover:text-nca-text px-3 py-2">Profile</Link>
            ) : (
              <>
                <Link to="/login" className="text-sm text-nca-text-muted hover:text-nca-text px-3 py-2">Sign In</Link>
                <Link to="/register"><Button size="sm">Get Started</Button></Link>
              </>
            )}
          </div>
          <button className="md:hidden p-2 rounded-lg text-nca-text-muted hover:text-nca-text hover:bg-nca-surface-hover" onClick={() => setMobileOpen(!mobileOpen)} aria-label="Toggle menu">
            {mobileOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
          </button>
        </div>
      </div>
      {mobileOpen && (
        <div className="md:hidden border-t border-nca-border bg-nca-surface px-4 py-3 space-y-1">
          {navLinks.map(link => (
            <Link key={link.to} to={link.to} onClick={() => setMobileOpen(false)} className={cn('block px-3 py-2 rounded-lg text-sm font-medium', isActive(link.to) ? 'text-nca-accent bg-nca-accent/10' : 'text-nca-text-muted hover:text-nca-text hover:bg-nca-surface-hover')}>
              {link.label}
            </Link>
          ))}
          <div className="pt-2 border-t border-nca-border mt-2 space-y-1">
            {isAuthenticated ? (
              <Link to="/profile" onClick={() => setMobileOpen(false)} className="block px-3 py-2 rounded-lg text-sm text-nca-text-muted hover:text-nca-text">Profile</Link>
            ) : (
              <>
                <Link to="/login" onClick={() => setMobileOpen(false)} className="block px-3 py-2 rounded-lg text-sm text-nca-text-muted hover:text-nca-text">Sign In</Link>
                <Link to="/register" onClick={() => setMobileOpen(false)} className="block px-3 py-2 rounded-lg text-sm text-nca-accent font-medium">Get Started</Link>
              </>
            )}
          </div>
        </div>
      )}
    </nav>
  )
}
