import adminClient from './adminClient'
import { mapPaginated } from '@/types/api'
import type { ApiResponse, LaravelPaginatedResponse, PaginatedData } from '@/types/api'
import type { Event, OrderBook, Trade, Order, Position } from '@/types/domain'

export interface AdminEventsParams {
  page?: number
  per_page?: number
  status?: string
  category?: string
  search?: string
  from?: string
  to?: string
}

export interface CreateEventPayload {
  title: string
  description?: string
  sub_category_ulid: string
  yes_initial_price: string
  fee_rate: string
  starts_at?: string
  ends_at?: string
  resolve_at?: string
}

export interface UpdateEventPayload extends Partial<CreateEventPayload> {}

export async function getAdminEvents(params: AdminEventsParams = {}): Promise<PaginatedData<Event>> {
  const res = await adminClient.get<LaravelPaginatedResponse<Event>>('/events', { params })
  return mapPaginated(res.data)
}

export async function getAdminEvent(ulid: string): Promise<Event> {
  const res = await adminClient.get<ApiResponse<Event>>(`/events/${ulid}`)
  return res.data.data
}

export async function createEvent(payload: CreateEventPayload): Promise<Event> {
  const res = await adminClient.post<ApiResponse<Event>>('/events', payload)
  return res.data.data
}

export async function updateEvent(ulid: string, payload: UpdateEventPayload): Promise<Event> {
  const res = await adminClient.put<ApiResponse<Event>>(`/events/${ulid}`, payload)
  return res.data.data
}

export async function openEvent(ulid: string): Promise<Event> {
  const res = await adminClient.put<ApiResponse<Event>>(`/events/${ulid}/open`)
  return res.data.data
}

export async function closeEvent(ulid: string): Promise<Event> {
  const res = await adminClient.put<ApiResponse<Event>>(`/events/${ulid}/close`)
  return res.data.data
}

export async function settleEvent(ulid: string, outcome: 'yes' | 'no'): Promise<Event> {
  const res = await adminClient.put<ApiResponse<Event>>(`/events/${ulid}/settle`, { outcome })
  return res.data.data
}

export async function getAdminEventOrderBook(ulid: string): Promise<OrderBook> {
  const res = await adminClient.get<ApiResponse<OrderBook>>(`/events/${ulid}/orderbook`)
  return res.data.data
}

export async function getAdminEventTrades(ulid: string, params: { page?: number; per_page?: number } = {}): Promise<PaginatedData<Trade>> {
  const res = await adminClient.get<LaravelPaginatedResponse<Trade>>(`/events/${ulid}/trades`, { params })
  return mapPaginated(res.data)
}

export async function getAdminEventOrders(ulid: string, params: { page?: number; per_page?: number; status?: string } = {}): Promise<PaginatedData<Order>> {
  const res = await adminClient.get<LaravelPaginatedResponse<Order>>(`/events/${ulid}/orders`, { params })
  return mapPaginated(res.data)
}

export async function getAdminEventPositions(ulid: string, params: { page?: number; per_page?: number } = {}): Promise<PaginatedData<Position>> {
  const res = await adminClient.get<LaravelPaginatedResponse<Position>>(`/events/${ulid}/positions`, { params })
  return mapPaginated(res.data)
}
