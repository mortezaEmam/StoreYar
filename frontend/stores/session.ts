import { defineStore } from 'pinia'

const TOKEN_KEY = 'storeyar_token'
const BUSINESS_KEY = 'storeyar_business_id'

export const useSessionStore = defineStore('session', {
  state: () => ({
    token: '' as string,
    businessId: '' as string,
    user: null as { id: string; email: string; name?: string } | null,
  }),
  getters: {
    isAuthenticated: (s) => Boolean(s.token),
  },
  actions: {
    hydrate() {
      if (!import.meta.client) return
      this.token = localStorage.getItem(TOKEN_KEY) || ''
      this.businessId = localStorage.getItem(BUSINESS_KEY) || ''
    },
    setToken(token: string) {
      this.token = token
      if (import.meta.client) localStorage.setItem(TOKEN_KEY, token)
    },
    setBusinessId(id: string) {
      this.businessId = id
      if (import.meta.client) localStorage.setItem(BUSINESS_KEY, id)
    },
    setUser(user: { id: string; email: string; name?: string } | null) {
      this.user = user
    },
    clear() {
      this.token = ''
      this.businessId = ''
      this.user = null
      if (import.meta.client) {
        localStorage.removeItem(TOKEN_KEY)
        localStorage.removeItem(BUSINESS_KEY)
      }
    },
  },
})
