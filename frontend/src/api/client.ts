import { API_CONFIG } from '../config/api.config'
import { ApiError } from '../utils/errors'

type HttpMethod = 'GET' | 'POST' | 'PUT' | 'DELETE'

let csrfTokenGetter: (() => string | null) | null = null

export function configureApiClient(config: { getCsrfToken: () => string | null }) {
  csrfTokenGetter = config.getCsrfToken
}

async function apiClient<T>(
  method: HttpMethod,
  endpoint: string,
  body?: unknown
): Promise<T> {
  const url = `${API_CONFIG.baseUrl}${endpoint}`

  const headers: Record<string, string> = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  }

  if (method !== 'GET' && csrfTokenGetter) {
    const token = csrfTokenGetter()
    if (token) {
      headers['X-CSRF-Token'] = token
    }
  }

  const options: RequestInit = {
    method,
    headers,
    credentials: 'include',
  }

  if (body !== undefined && method !== 'GET') {
    options.body = JSON.stringify(body)
  }

  let response: Response

  try {
    response = await fetch(url, options)
  } catch {
    throw new ApiError('NETWORK_ERROR', 'Unable to connect to the server.', 0)
  }

  if (response.status === 204) {
    return {} as T
  }

  let data: unknown

  try {
    data = await response.json()
  } catch {
    throw new ApiError('PARSE_ERROR', 'Invalid server response.', response.status)
  }

  if (!response.ok) {
    const errorData = data as { success?: false; error?: { code?: string; message?: string } } | undefined
    const code = errorData?.error?.code || 'UNKNOWN_ERROR'
    const message = errorData?.error?.message || 'An error occurred.'
    throw new ApiError(code, message, response.status)
  }

  const successData = data as { success?: boolean; data?: T; message?: string }
  return successData.data as T
}

export const api = {
  get: <T>(endpoint: string) => apiClient<T>('GET', endpoint),
  post: <T>(endpoint: string, body?: unknown) => apiClient<T>('POST', endpoint, body),
  put: <T>(endpoint: string, body?: unknown) => apiClient<T>('PUT', endpoint, body),
  del: <T>(endpoint: string) => apiClient<T>('DELETE', endpoint),
}
