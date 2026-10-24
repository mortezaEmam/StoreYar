import { useSessionStore } from '~/stores/session'

type ApiOptions = {
  method?: string
  body?: unknown
  idempotencyKey?: string
  headers?: Record<string, string>
}

export function useApi() {
  const config = useRuntimeConfig()
  const session = useSessionStore()
  const base = (config.public.apiBase as string) || 'http://localhost:8000/api'

  async function request<T = any>(path: string, options: ApiOptions = {}): Promise<T> {
    const headers: Record<string, string> = {
      Accept: 'application/json',
      ...(options.body !== undefined ? { 'Content-Type': 'application/json' } : {}),
      ...(options.headers || {}),
    }
    if (session.token) headers.Authorization = `Bearer ${session.token}`
    if (session.businessId) headers['X-Business-Id'] = session.businessId
    if (options.idempotencyKey) headers['Idempotency-Key'] = options.idempotencyKey

    const res = await fetch(`${base}${path}`, {
      method: options.method || 'GET',
      headers,
      body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
    })

    const text = await res.text()
    let data: any = null
    try {
      data = text ? JSON.parse(text) : null
    } catch {
      data = text
    }

    if (!res.ok) {
      const msg =
        (data && (data.message || data.error)) ||
        (typeof data === 'string' && data) ||
        `HTTP ${res.status}`
      throw new Error(typeof msg === 'string' ? msg : JSON.stringify(msg))
    }

    return data as T
  }

  return {
    get: <T = any>(path: string) => request<T>(path, { method: 'GET' }),
    post: <T = any>(path: string, body?: unknown, idempotencyKey?: string) =>
      request<T>(path, { method: 'POST', body, idempotencyKey }),
    patch: <T = any>(path: string, body?: unknown, idempotencyKey?: string) =>
      request<T>(path, { method: 'PATCH', body, idempotencyKey }),
    put: <T = any>(path: string, body?: unknown, idempotencyKey?: string) =>
      request<T>(path, { method: 'PUT', body, idempotencyKey }),
    del: <T = any>(path: string, idempotencyKey?: string) =>
      request<T>(path, { method: 'DELETE', idempotencyKey }),
  }
}
