export interface ApiResponse<T> {
  data: T
  message?: string | null
}

export interface LaravelMeta {
  current_page: number
  last_page: number
  total: number
  per_page: number
}

export interface LaravelPaginatedResponse<T> {
  data: T[]
  meta: LaravelMeta
}

export interface PaginatedData<T> {
  items: T[]
  total: number
  page: number
  per_page: number
  last_page: number
}

export function mapPaginated<T>(res: LaravelPaginatedResponse<T>): PaginatedData<T> {
  return {
    items: res.data,
    total: res.meta.total,
    page: res.meta.current_page,
    per_page: res.meta.per_page,
    last_page: res.meta.last_page,
  }
}

export interface LoginPayload {
  email: string
  password: string
}

export interface RegisterPayload {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export interface AuthResponse {
  token: string
  user: import('./domain').User
}

export interface AdminAuthResponse {
  token: string
  admin: import('./domain').Admin
}

export interface CreateOrderPayload {
  event_ulid: string
  side: 'yes' | 'no'
  price: string
  quantity: string
}

export interface CreateWithdrawalPayload {
  amount: string
  network: string
  destination_address: string
  memo?: string
}

export interface SubmitDepositPayload {
  tx_id: string
  network: string
}

export interface CreateEventPayload {
  title: string
  description?: string
  sub_category_ulid: string
  yes_initial_price: string
  fee_rate: string
  starts_at: string
  ends_at: string
  resolve_at: string
}

export interface UpdateEventPayload {
  title?: string
  description?: string
  yes_initial_price?: string
  fee_rate?: string
  starts_at?: string
  ends_at?: string
  resolve_at?: string
}

export interface SettleEventPayload {
  outcome: 'yes' | 'no'
}

export interface CreateCategoryPayload {
  name: string
  slug: string
  is_active: boolean
}

export interface CreateSubCategoryPayload {
  category_ulid: string
  name: string
  slug: string
  is_active: boolean
}
