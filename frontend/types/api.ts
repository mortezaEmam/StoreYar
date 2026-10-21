export interface UserSummary { id: string; email: string; name?: string }
export interface TokenData { token: string; user: UserSummary }
export interface ApiData<T> { data: T }
export interface Organization { id: string; name: string }
export interface Product {
  id: string; name: string; sku: string
  unit_price_amount: number; currency: string; status?: string
}
export interface Order {
  id: string; status: string; lines?: any[]
  total_amount?: number; currency?: string
}
