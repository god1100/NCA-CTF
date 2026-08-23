import type { User } from './user'

export interface LoginCredentials {
  identifier: string
  password: string
}

export interface RegisterCredentials {
  username: string
  email: string
  password: string
  full_name?: string
}

export interface LoginResponse {
  user: User
  csrf_token: string
}

export interface MeResponse {
  user: User
  csrf_token: string
}
