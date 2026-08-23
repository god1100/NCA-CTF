import { Link } from 'react-router-dom'
import { RegisterForm } from '../components/auth/RegisterForm'
import { Shield } from 'lucide-react'

export function RegisterPage() {
  return (
    <div className="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4 py-12">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <div className="w-12 h-12 rounded-xl bg-nca-accent/10 border border-nca-accent/30 flex items-center justify-center mx-auto mb-4">
            <Shield className="w-6 h-6 text-nca-accent" />
          </div>
          <h1 className="text-2xl font-bold text-nca-text">Create account</h1>
          <p className="text-sm text-nca-text-muted mt-1">Join NCA-CTF and start competing</p>
        </div>
        <div className="p-6 rounded-xl border border-nca-border bg-nca-surface">
          <RegisterForm />
        </div>
        <p className="text-center text-sm text-nca-text-muted mt-6">
          Already have an account?{' '}
          <Link to="/login" className="text-nca-accent hover:underline">Sign in</Link>
        </p>
      </div>
    </div>
  )
}
