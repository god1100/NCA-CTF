export interface Team {
  id: number
  name: string
  slug: string
  status: string
  created_at: string
}

export interface TeamMember {
  user_id: number
  username: string | null
  full_name: string | null
  is_captain: boolean
  joined_at: string
}

export interface TeamInvitation {
  id: number
  team_id: number
  email: string
  token?: string
  status: string
  created_at: string
  expires_at: string
}

export interface MyTeamResponse {
  team: Team | null
  is_captain: boolean
  joined_at: string | null
}
