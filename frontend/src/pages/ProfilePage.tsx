import { useAuth } from '../hooks/useAuth'
import { Card, CardHeader, CardTitle, CardContent } from '../components/ui/Card'
import { Badge } from '../components/ui/Badge'
import { User, Mail, Shield, Calendar, Clock } from 'lucide-react'
import { formatDate, formatDateTime } from '../utils/formatters'

export function ProfilePage() {
  const { user } = useAuth()

  if (!user) return null

  const fields = [
    { icon: User, label: 'Username', value: user.username },
    { icon: Mail, label: 'Email', value: user.email },
    { icon: User, label: 'Full Name', value: user.full_name || '—' },
    { icon: Shield, label: 'Role', value: user.role || '—' },
    { icon: Shield, label: 'Status', value: <Badge variant={user.status === 'active' ? 'success' : 'default'}>{user.status}</Badge> },
    { icon: Calendar, label: 'Joined', value: formatDate(user.created_at) },
    { icon: Clock, label: 'Last Login', value: formatDateTime(user.last_login_at) },
  ]

  return (
    <div className="space-y-6 max-w-2xl">
      <div>
        <h1 className="text-2xl font-bold text-nca-text">Profile</h1>
        <p className="text-sm text-nca-text-muted mt-1">Your account information.</p>
      </div>

      <Card>
        <CardHeader>
          <div className="flex items-center gap-4">
            <div className="w-16 h-16 rounded-full bg-nca-accent/10 border border-nca-accent/30 flex items-center justify-center">
              <span className="text-2xl font-bold text-nca-accent">{user.username.charAt(0).toUpperCase()}</span>
            </div>
            <div>
              <CardTitle>{user.username}</CardTitle>
              <p className="text-sm text-nca-text-muted">{user.email}</p>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {fields.map(f => (
              <div key={f.label} className="flex items-center justify-between py-2 border-b border-nca-border last:border-0">
                <div className="flex items-center gap-2 text-sm text-nca-text-muted">
                  <f.icon className="w-4 h-4" />
                  {f.label}
                </div>
                <div className="text-sm text-nca-text">{f.value}</div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      <div className="p-4 rounded-lg bg-nca-warning/10 border border-nca-warning/30">
        <p className="text-sm text-nca-warning">Profile editing is not yet available. Contact an administrator if you need to update your information.</p>
      </div>
    </div>
  )
}
