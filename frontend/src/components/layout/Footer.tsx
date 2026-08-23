import { Link } from 'react-router-dom'
import { Shield } from 'lucide-react'
import { NCAContacts } from '../contacts/NCAContacts'

export function Footer() {
  return (
    <footer className="border-t border-nca-border bg-nca-surface">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div>
            <div className="flex items-center gap-2.5 mb-4">
              <div className="w-8 h-8 rounded-lg bg-nca-accent/10 border border-nca-accent/30 flex items-center justify-center">
                <Shield className="w-4.5 h-4.5 text-nca-accent" />
              </div>
              <span className="font-bold text-nca-text">NCA-CTF</span>
            </div>
            <p className="text-sm text-nca-text-muted leading-relaxed">
              A private cybersecurity challenge platform for NCA Batch 4 participants. Think. Exploit. Capture.
            </p>
          </div>
          <div>
            <h4 className="text-sm font-semibold text-nca-text mb-4">Platform</h4>
            <ul className="space-y-2.5">
              <li><Link to="/challenges" className="text-sm text-nca-text-muted hover:text-nca-accent transition-colors">Challenges</Link></li>
              <li><Link to="/leaderboard" className="text-sm text-nca-text-muted hover:text-nca-accent transition-colors">Leaderboard</Link></li>
              <li><Link to="/team" className="text-sm text-nca-text-muted hover:text-nca-accent transition-colors">Team</Link></li>
            </ul>
          </div>
          <div><NCAContacts compact /></div>
        </div>
        <div className="mt-10 pt-6 border-t border-nca-border flex flex-col sm:flex-row items-center justify-between gap-4">
          <p className="text-xs text-nca-text-dim">&copy; {new Date().getFullYear()} NCA Group. All rights reserved.</p>
          <span className="text-xs text-nca-text-dim">NCA Batch 4 CTF Platform</span>
        </div>
      </div>
    </footer>
  )
}
