import { api } from './client'
import type { LoginCredentials, RegisterCredentials, LoginResponse, MeResponse, User } from '../types'

export const authService = {
  register: (credentials: RegisterCredentials) =>
    api.post<{ user: User }>('/auth/register', credentials),

  login: (credentials: LoginCredentials) =>
    api.post<LoginResponse>('/auth/login', credentials),

  logout: () =>
    api.post<{}>('/auth/logout'),

  me: () =>
    api.get<MeResponse>('/auth/me'),
}
