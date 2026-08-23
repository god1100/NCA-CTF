import { Link } from 'react-router-dom'
import { Button } from '../components/ui/Button'
import { Home, ShieldAlert } from 'lucide-react'

export function ForbiddenPage() {
  return (
    <div className="min-h-screen bg-nca-bg flex items-center justify-center px-4">
      <div className="text-center">
        <div className="w-16 h-16 rounded-xl bg-nca-warning/10 flex items-center justify-center mx-auto mb-6">
          <ShieldAlert className="w-8 h-8 text-nca-warning" />
        </div>
        <h1 className="text-4xl font-bold text-nca-text">403</h1>
        <p className="text-lg text-nca-text-muted mt-2">Access denied</p>
        <p className="text-sm text-nca-text-dim mt-1">You do not have permission to access this resource.</p>
        <div className="mt-6">
          <Link to="/"><Button leftIcon={<Home className="w-4 h-4" />}>Go Home</Button></Link>
        </div>
      </div>
    </div>
  )
}
