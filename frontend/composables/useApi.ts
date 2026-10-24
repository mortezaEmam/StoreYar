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
  const base = String(config.public.apiBase || 'http://localhost:8000/api').replace(/\/$/, '')

  async function request<T = any>(path: string, options: ApiOptions = {}): Promise<T> {
    const headers: Record<string, string> = {
      Accept: 'application/json',
      ...(options.body !== undefined ? { 'Content-Type': 'application/json' } : {}),
      ...(options.headers || {}),
    }
    if (session.token) headers.Authorization = `Bearer ${session.token}`
    if (session.businessId) headers['X-Business-Id'] = session.businessId
    if (options.idempotencyKey) headers['Idempotency-Key'] = options.idempotencyKey

    const url = path.startsWith('http') ? path : `${base}${path.startsWith('/') ? path : `/${path}`}`

    let res: Response
    try {
      res = await fetch(url, {
        method: options.method || 'GET',
        headers,
        body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
      })
    } catch (e: unknown) {
      const msg = e instanceof Error ? e.message : 'Network error'
      throw new Error(
        `Cannot reach API at ${base}. Is Laravel running (php artisan serve)? (${msg})`,
      )
    }

    const text = await res.text()
    let data: any = null
    try {
      data = text ? JSON.parse(text) : null
    } catch {
      data = text
    }

    if (!res.ok) {
      const msg =
        (data && (data.message || data.error || data.exception)) ||
        (typeof data === 'string' && data.slice(0, 200)) ||
        `HTTP ${res.status}`
      throw new Error(typeof msg === 'string' ? msg : JSON.stringify(msg))
    }

    return data as T
  }

  /** Laravel JsonResource usually wraps as { data: T }; sometimes T is bare. */
  function unwrap<T = any>(res: any): T {
    if (res && typeof res === 'object' && 'data' in res && res.data !== undefined) {
      return res.data as T
    }
    return res as T
  }

  return {
    base,
    unwrap,
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
