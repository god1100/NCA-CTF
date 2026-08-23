export interface Category {
  id: number
  name: string
  slug: string
  description: string | null
}

export interface ChallengeFile {
  id: number
  name: string
  size: number
  sha256: string | null
}

export interface ChallengeHint {
  id: number
  title: string | null
  content?: string
  point_penalty: number
}

export interface Challenge {
  id: number
  title: string
  slug: string
  description: string
  category: string | null
  difficulty: string
  points: number
  status: string
  deployment_type: string
  solved: boolean
  files?: ChallengeFile[]
  hints?: ChallengeHint[]
}

export interface ChallengeListResponse {
  challenges: Challenge[]
  pagination: {
    page: number
    per_page: number
    total: number
    total_pages: number
  }
}

export interface ChallengeFilters {
  page?: number
  per_page?: number
  category?: string
  difficulty?: string
}
