import { createContext, useState, useCallback, useEffect, type ReactNode } from 'react'
import { configureApiClient } from '../api/client'
import { authService } from '../api/auth.service'
import type { User } from '../types'

interface AuthContextValue {
  user: User | null
  csrfToken: string | null
  isAuthenticated: boolean
  isLoading: boolean
  login: (identifier: string, password: string) => Promise<void>
  logout: () => Promise<void>
  refresh: () => Promise<void>
}

export const AuthContext = createContext<AuthContextValue | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [csrfToken, setCsrfToken] = useState<string | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  const refresh = useCallback(async () => {
    try {
      const response = await authService.me()
      setUser(response.user)
      setCsrfToken(response.csrf_token)
    } catch {
      setUser(null)
      setCsrfToken(null)
    }
  }, [])

  useEffect(() => {
    configureApiClient({ getCsrfToken: () => csrfToken })
  }, [csrfToken])

  useEffect(() => {
    refresh().finally(() => setIsLoading(false))
  }, [refresh])

  const login = useCallback(async (identifier: string, password: string) => {
    const response = await authService.login({ identifier, password })
    setUser(response.user)
    setCsrfToken(response.csrf_token)
  }, [])

  const logout = useCallback(async () => {
    try { await authService.logout() } finally {
      setUser(null)
      setCsrfToken(null)
    }
  }, [])

  return (
    <AuthContext.Provider value={{ user, csrfToken, isAuthenticated: user !== null, isLoading, login, logout, refresh }}>
      {children}
    </AuthContext.Provider>
  )
}
