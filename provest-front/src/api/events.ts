import client from './client'
import { mapPaginated } from '@/types/api'
import type { ApiResponse, LaravelPaginatedResponse, PaginatedData } from '@/types/api'
import type { Event, OrderBook, Trade, Category } from '@/types/domain'

export interface EventsParams {
  page?: number
  per_page?: number
  status?: string
  category?: string
  search?: string
}

export async function getEvents(params: EventsParams = {}): Promise<PaginatedData<Event>> {
  const res = await client.get<LaravelPaginatedResponse<Event>>('/events', { params })
  return mapPaginated(res.data)
}

export async function getEvent(ulid: string): Promise<Event> {
  const res = await client.get<ApiResponse<Event>>(`/events/${ulid}`)
  return res.data.data
}

export async function getOrderBook(ulid: string): Promise<OrderBook> {
  const res = await client.get<ApiResponse<OrderBook>>(`/events/${ulid}/orderbook`)
  return res.data.data
}

export async function getTrades(ulid: string): Promise<Trade[]> {
  const res = await client.get<ApiResponse<Trade[]>>(`/events/${ulid}/trades`)
  return res.data.data
}

export async function getCategories(): Promise<Category[]> {
  const res = await client.get<ApiResponse<Category[]>>('/categories')
  return res.data.data
}
