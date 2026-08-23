import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../../hooks/useAuth'
import { Input } from '../ui/Input'
import { Button } from '../ui/Button'
import { Alert } from '../ui/Alert'
import { Eye, EyeOff } from 'lucide-react'
import { getUserFriendlyMessage } from '../../utils/errors'

export function RegisterForm() {
  const navigate = useNavigate()
  const { login } = useAuth()
  const [form, setForm] = useState({ username: '', email: '', full_name: '', password: '', confirm: '' })
  const [showPassword, setShowPassword] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({})

  const update = (key: string, value: string) => {
    setForm(prev => ({ ...prev, [key]: value }))
    setFieldErrors(prev => { const n = { ...prev }; delete n[key]; return n })
  }

  const validate = () => {
    const errs: Record<string, string> = {}
    if (!form.username.trim()) errs.username = 'Username is required.'
    else if (form.username.length < 3) errs.username = 'Username must be at least 3 characters.'
    if (!form.email.trim()) errs.email = 'Email is required.'
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) errs.email = 'Please enter a valid email.'
    if (!form.password) errs.password = 'Password is required.'
    else if (form.password.length < 8) errs.password = 'Password must be at least 8 characters.'
    if (form.password !== form.confirm) errs.confirm = 'Passwords do not match.'
    setFieldErrors(errs)
    return Object.keys(errs).length === 0
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError(null)
    if (!validate()) return
    setIsLoading(true)
    try {
      const { confirm: _, ...payload } = form
      await fetch('/api/v1/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(payload),
      })
      await login(form.username, form.password)
      navigate('/dashboard')
    } catch (err) {
      setError(getUserFriendlyMessage(err))
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      {error && <Alert variant="error">{error}</Alert>}
      <Input label="Username" value={form.username} onChange={e => update('username', e.target.value)} error={fieldErrors.username} placeholder="Choose a username" autoComplete="username" />
      <Input label="Email" type="email" value={form.email} onChange={e => update('email', e.target.value)} error={fieldErrors.email} placeholder="you@example.com" autoComplete="email" />
      <Input label="Full Name (optional)" value={form.full_name} onChange={e => update('full_name', e.target.value)} placeholder="Your full name" autoComplete="name" />
      <div className="relative">
        <Input label="Password" type={showPassword ? 'text' : 'password'} value={form.password} onChange={e => update('password', e.target.value)} error={fieldErrors.password} placeholder="Min. 8 characters" autoComplete="new-password" />
        <button type="button" onClick={() => setShowPassword(!showPassword)} className="absolute right-3 top-[34px] text-nca-text-dim hover:text-nca-text" aria-label={showPassword ? 'Hide password' : 'Show password'}>
          {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
        </button>
      </div>
      <Input label="Confirm Password" type="password" value={form.confirm} onChange={e => update('confirm', e.target.value)} error={fieldErrors.confirm} placeholder="Repeat your password" autoComplete="new-password" />
      <Button type="submit" isLoading={isLoading} className="w-full">Create Account</Button>
    </form>
  )
}
