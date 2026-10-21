import { useSessionStore } from '~/stores/session'

export function useApi() {
  const config = useRuntimeConfig()
  const session = useSessionStore()

  function baseHeaders(extra?: HeadersInit): Headers {
    const h = new Headers(extra)
    h.set('Accept', 'application/json')
    if (!h.has('Content-Type')) h.set('Content-Type', 'application/json')
    if (session.token) h.set('Authorization', `Bearer ${session.token}`)
    if (session.businessId) h.set('X-Business-Id', session.businessId)
    return h
  }

  async function request<T>(
    path: string,
    options: RequestInit & { idempotencyKey?: string } = {},
  ): Promise<T> {
    const { idempotencyKey, ...init } = options
    const headers = baseHeaders(init.headers)
    if (idempotencyKey) headers.set('Idempotency-Key', idempotencyKey)

    const res = await fetch(`${config.public.apiBase}${path}`, { ...init, headers })

    if (res.status === 401) {
      session.clear()
      if (import.meta.client) await navigateTo('/login')
      throw new Error('Unauthorized')
    }

    if (!res.ok) {
      let message = `API ${res.status}`
      try {
        const body = await res.json()
        if (body?.message) message = body.message
      } catch { /* ignore */ }
      throw new Error(message)
    }

    if (res.status === 204) return undefined as T
    return (await res.json()) as T
  }

  return {
    get: <T>(path: string) => request<T>(path, { method: 'GET' }),
    post: <T>(path: string, body?: unknown, idempotencyKey?: string) =>
      request<T>(path, {
        method: 'POST',
        body: body !== undefined ? JSON.stringify(body) : undefined,
        idempotencyKey,
      }),
    patch: <T>(path: string, body?: unknown) =>
      request<T>(path, {
        method: 'PATCH',
        body: body !== undefined ? JSON.stringify(body) : undefined,
      }),
    del: <T>(path: string) => request<T>(path, { method: 'DELETE' }),
  }
}
