import { Link } from 'react-router-dom'
import { LoginForm } from '../components/auth/LoginForm'
import { Shield } from 'lucide-react'

export function LoginPage() {
  return (
    <div className="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4 py-12">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <div className="w-12 h-12 rounded-xl bg-nca-accent/10 border border-nca-accent/30 flex items-center justify-center mx-auto mb-4">
            <Shield className="w-6 h-6 text-nca-accent" />
          </div>
          <h1 className="text-2xl font-bold text-nca-text">Welcome back</h1>
          <p className="text-sm text-nca-text-muted mt-1">Sign in to your NCA-CTF account</p>
        </div>
        <div className="p-6 rounded-xl border border-nca-border bg-nca-surface">
          <LoginForm />
        </div>
        <p className="text-center text-sm text-nca-text-muted mt-6">
          Don't have an account?{' '}
          <Link to="/register" className="text-nca-accent hover:underline">Create one</Link>
        </p>
      </div>
    </div>
  )
}
