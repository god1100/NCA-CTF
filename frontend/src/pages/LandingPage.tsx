import { Link } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'
import { Button } from '../components/ui/Button'
import { NCAContacts } from '../components/contacts/NCAContacts'
import { Shield, Flag, Globe, Terminal, Users, Zap, ChevronRight, Binary, KeyRound, Server } from 'lucide-react'

export function LandingPage() {
  const { isAuthenticated } = useAuth()

  const categories = [
    { icon: Globe, name: 'Web', desc: 'Exploit web applications and APIs' },
    { icon: Terminal, name: 'Pwn', desc: 'Binary exploitation and reverse engineering' },
    { icon: KeyRound, name: 'Crypto', desc: 'Cryptographic challenges and ciphers' },
    { icon: Server, name: 'General', desc: 'Forensics, OSINT, and misc challenges' },
  ]

  const features = [
    { icon: Shield, title: 'Private Platform', desc: 'Exclusively for NCA Batch 4 participants.' },
    { icon: Users, title: 'Team Based', desc: 'Collaborate with your team to solve challenges.' },
    { icon: Flag, title: 'Real Challenges', desc: 'Hands-on cybersecurity challenges across multiple domains.' },
    { icon: Zap, title: 'Compete', desc: 'Track your progress on the live leaderboard.' },
  ]

  const steps = [
    { num: '01', title: 'Register', desc: 'Create your account and verify your identity.' },
    { num: '02', title: 'Join a Team', desc: 'Create or join a team to compete together.' },
    { num: '03', title: 'Solve Challenges', desc: 'Find vulnerabilities, exploit systems, capture flags.' },
    { num: '04', title: 'Climb the Board', desc: 'Submit flags and watch your team rise in the rankings.' },
  ]

  return (
    <div className="flex flex-col">
      {/* Hero */}
      <section className="relative overflow-hidden border-b border-nca-border">
        <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-nca-accent/5 via-transparent to-transparent" />
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28 relative">
          <div className="max-w-3xl">
            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-nca-accent/10 border border-nca-accent/20 text-nca-accent text-xs font-medium mb-6">
              <Binary className="w-3.5 h-3.5" />
              NCA Batch 4 Private CTF Platform
            </div>
            <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-nca-text leading-tight">
              Think. <span className="text-gradient">Exploit.</span> Capture.
            </h1>
            <p className="mt-6 text-lg sm:text-xl text-nca-text-muted leading-relaxed max-w-2xl">
              A private cybersecurity competition platform built for NCA Batch 4. 
              Solve real-world challenges, collaborate with your team, and prove your skills.
            </p>
            <div className="mt-8 flex flex-wrap gap-4">
              {isAuthenticated ? (
                <Link to="/dashboard"><Button size="lg" rightIcon={<ChevronRight className="w-4 h-4" />}>Go to Dashboard</Button></Link>
              ) : (
                <>
                  <Link to="/register"><Button size="lg" rightIcon={<ChevronRight className="w-4 h-4" />}>Get Started</Button></Link>
                  <Link to="/login"><Button size="lg" variant="secondary">Sign In</Button></Link>
                </>
              )}
            </div>
          </div>
        </div>
      </section>

      {/* Features */}
      <section className="py-16 sm:py-20 border-b border-nca-border">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <h2 className="text-2xl sm:text-3xl font-bold text-nca-text">Platform Features</h2>
            <p className="mt-3 text-nca-text-muted">Everything you need for a competitive CTF experience.</p>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {features.map(f => (
              <div key={f.title} className="p-6 rounded-xl border border-nca-border bg-nca-surface hover:border-nca-accent/30 transition-colors">
                <div className="w-10 h-10 rounded-lg bg-nca-accent/10 flex items-center justify-center mb-4">
                  <f.icon className="w-5 h-5 text-nca-accent" />
                </div>
                <h3 className="font-semibold text-nca-text mb-1">{f.title}</h3>
                <p className="text-sm text-nca-text-muted">{f.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Categories */}
      <section className="py-16 sm:py-20 border-b border-nca-border">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <h2 className="text-2xl sm:text-3xl font-bold text-nca-text">Challenge Categories</h2>
            <p className="mt-3 text-nca-text-muted">Multiple domains to test your cybersecurity expertise.</p>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {categories.map(c => (
              <div key={c.name} className="p-6 rounded-xl border border-nca-border bg-nca-surface text-center hover:border-nca-accent/30 transition-colors">
                <div className="w-12 h-12 rounded-xl bg-nca-accent/10 flex items-center justify-center mx-auto mb-4">
                  <c.icon className="w-6 h-6 text-nca-accent" />
                </div>
                <h3 className="font-semibold text-nca-text mb-1">{c.name}</h3>
                <p className="text-sm text-nca-text-muted">{c.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* How It Works */}
      <section className="py-16 sm:py-20 border-b border-nca-border">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <h2 className="text-2xl sm:text-3xl font-bold text-nca-text">How It Works</h2>
            <p className="mt-3 text-nca-text-muted">Get started in four simple steps.</p>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {steps.map(s => (
              <div key={s.num} className="relative p-6 rounded-xl border border-nca-border bg-nca-surface">
                <span className="text-3xl font-bold text-nca-accent/20">{s.num}</span>
                <h3 className="font-semibold text-nca-text mt-2 mb-1">{s.title}</h3>
                <p className="text-sm text-nca-text-muted">{s.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Stats placeholder */}
      <section className="py-16 sm:py-20 border-b border-nca-border">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <h2 className="text-2xl sm:text-3xl font-bold text-nca-text">Platform Statistics</h2>
            <p className="mt-3 text-nca-text-muted">Live competition metrics will appear here once the event begins.</p>
          </div>
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-6">
            {[{ label: 'Challenges', val: '—' }, { label: 'Teams', val: '—' }, { label: 'Participants', val: '—' }, { label: 'Flags Captured', val: '—' }].map(s => (
              <div key={s.label} className="text-center p-6 rounded-xl border border-nca-border bg-nca-surface">
                <p className="text-3xl font-bold text-nca-accent">{s.val}</p>
                <p className="text-sm text-nca-text-muted mt-1">{s.label}</p>
                <p className="text-xs text-nca-text-dim mt-1">Coming Soon</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="py-16 sm:py-20 border-b border-nca-border">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-2xl sm:text-3xl font-bold text-nca-text">Ready to compete?</h2>
          <p className="mt-3 text-nca-text-muted max-w-xl mx-auto">Join NCA-CTF today and start solving challenges with your team.</p>
          <div className="mt-8">
            {isAuthenticated ? (
              <Link to="/challenges"><Button size="lg">Browse Challenges</Button></Link>
            ) : (
              <Link to="/register"><Button size="lg">Create Account</Button></Link>
            )}
          </div>
        </div>
      </section>

      {/* Contacts */}
      <section className="py-16 sm:py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <h2 className="text-2xl sm:text-3xl font-bold text-nca-text">Contact</h2>
            <p className="mt-3 text-nca-text-muted">Get in touch with the NCA-CTF team.</p>
          </div>
          <div className="max-w-md mx-auto">
            <NCAContacts />
          </div>
        </div>
      </section>
    </div>
  )
}
