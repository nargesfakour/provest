import client from './client'
import { mapPaginated } from '@/types/api'
import type { ApiResponse, LaravelPaginatedResponse, PaginatedData, CreateOrderPayload } from '@/types/api'
import type { Order } from '@/types/domain'

export interface OrdersParams {
  page?: number
  per_page?: number
  status?: string
  event_ulid?: string
}

export async function createOrder(payload: CreateOrderPayload): Promise<Order> {
  const res = await client.post<ApiResponse<Order>>('/orders', payload)
  return res.data.data
}

export async function getOrders(params: OrdersParams = {}): Promise<PaginatedData<Order>> {
  const res = await client.get<LaravelPaginatedResponse<Order>>('/orders', { params })
  return mapPaginated(res.data)
}

export async function cancelOrder(ulid: string): Promise<void> {
  await client.delete(`/orders/${ulid}`)
}
