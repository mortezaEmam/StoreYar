/**
 * Minimal API client: attaches bearer token and X-Business-Id.
 */
export function useApi() {
  const config = useRuntimeConfig()

  function headers(): Record<string, string> {
    const h: Record<string, string> = {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    }
    if (import.meta.client) {
      const token = localStorage.getItem('storeyar_token')
      const businessId = localStorage.getItem('storeyar_business_id')
      if (token) h.Authorization = `Bearer ${token}`
      if (businessId) h['X-Business-Id'] = businessId
    }
    return h
  }

  async function api<T>(path: string, opts: RequestInit = {}): Promise<T> {
    const res = await fetch(`${config.public.apiBase}${path}`, {
      ...opts,
      headers: { ...headers(), ...(opts.headers as Record<string, string> | undefined) },
    })
    if (!res.ok) {
      throw new Error(`API ${res.status}`)
    }
    return res.json() as Promise<T>
  }

  return { api, headers }
}
