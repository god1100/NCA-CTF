import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../../hooks/useAuth'
import { Input } from '../ui/Input'
import { Button } from '../ui/Button'
import { Alert } from '../ui/Alert'
import { Eye, EyeOff } from 'lucide-react'
import { getUserFriendlyMessage } from '../../utils/errors'

export function LoginForm() {
  const navigate = useNavigate()
  const { login } = useAuth()
  const [identifier, setIdentifier] = useState('')
  const [password, setPassword] = useState('')
  const [showPassword, setShowPassword] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError(null)
    if (!identifier.trim() || !password.trim()) {
      setError('Please enter both identifier and password.')
      return
    }
    setIsLoading(true)
    try {
      await login(identifier.trim(), password)
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
      <Input label="Username or Email" type="text" value={identifier} onChange={e => setIdentifier(e.target.value)} placeholder="Enter your username or email" autoComplete="username" />
      <div className="relative">
        <Input label="Password" type={showPassword ? 'text' : 'password'} value={password} onChange={e => setPassword(e.target.value)} placeholder="Enter your password" autoComplete="current-password" />
        <button type="button" onClick={() => setShowPassword(!showPassword)} className="absolute right-3 top-[34px] text-nca-text-dim hover:text-nca-text" aria-label={showPassword ? 'Hide password' : 'Show password'}>
          {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
        </button>
      </div>
      <Button type="submit" isLoading={isLoading} className="w-full">Sign In</Button>
    </form>
  )
}
