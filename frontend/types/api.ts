export interface UserSummary {
  id: string
  email: string
  name?: string
}

export interface TokenData {
  token: string
  user: UserSummary
}

export interface ApiData<T> {
  data: T
}

export interface PageMeta {
  page: number
  per_page: number
  total: number
  last_page: number
}

export interface Paginated<T> {
  data: T[]
  meta: PageMeta
}

export interface Organization {
  id: string
  name: string
}

export interface Product {
  id: string
  name: string
  sku: string
  unit_price_amount: number
  currency: string
  status?: string
}

export interface OrderLine {
  product_id: string
  quantity: number
  unit_price_amount?: number
  currency?: string
}

export interface Order {
  id: string
  status: string
  lines?: OrderLine[]
  total_amount?: number
  currency?: string
}

export interface StockRow {
  id?: string
  product_id: string
  quantity: number
  reserved_quantity?: number
}

export interface Payment {
  id: string
  order_id: string
  amount: number
  currency: string
  status: string
}

export interface Shipment {
  id: string
  order_id: string
  status: string
  tracking_number?: string | null
}
