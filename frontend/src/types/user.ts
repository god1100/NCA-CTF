export interface User {
  id: number
  username: string
  email: string
  full_name: string | null
  role: string | null
  status: string
  created_at: string
  last_login_at: string | null
}
