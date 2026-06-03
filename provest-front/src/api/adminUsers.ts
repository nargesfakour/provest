import adminClient from './adminClient'
import { mapPaginated } from '@/types/api'
import type { ApiResponse, LaravelPaginatedResponse, PaginatedData } from '@/types/api'
import type { User, Order, Position, WalletTransaction } from '@/types/domain'
import type { AdminDeposit } from '@/types/domain'

export interface AdminUsersParams {
  page?: number
  per_page?: number
  search?: string
  status?: string
}

export async function getAdminUsers(params: AdminUsersParams = {}): Promise<PaginatedData<User>> {
  const res = await adminClient.get<LaravelPaginatedResponse<User>>('/users', { params })
  return mapPaginated(res.data)
}

export async function getAdminUser(ulid: string): Promise<User> {
  const res = await adminClient.get<ApiResponse<User>>(`/users/${ulid}`)
  return res.data.data
}

export async function activateUser(ulid: string): Promise<User> {
  const res = await adminClient.put<ApiResponse<User>>(`/users/${ulid}/activate`)
  return res.data.data
}

export async function deactivateUser(ulid: string): Promise<User> {
  const res = await adminClient.put<ApiResponse<User>>(`/users/${ulid}/deactivate`)
  return res.data.data
}

export async function getAdminUserOrders(
  ulid: string,
  params: { page?: number; per_page?: number; status?: string } = {},
): Promise<PaginatedData<Order>> {
  const res = await adminClient.get<LaravelPaginatedResponse<Order>>(`/users/${ulid}/orders`, { params })
  return mapPaginated(res.data)
}

export async function getAdminUserPositions(
  ulid: string,
  params: { page?: number; per_page?: number } = {},
): Promise<PaginatedData<Position>> {
  const res = await adminClient.get<LaravelPaginatedResponse<Position>>(`/users/${ulid}/positions`, { params })
  return mapPaginated(res.data)
}

export async function getAdminUserTransactions(
  ulid: string,
  params: { page?: number; per_page?: number } = {},
): Promise<PaginatedData<WalletTransaction>> {
  const res = await adminClient.get<LaravelPaginatedResponse<WalletTransaction>>(`/users/${ulid}/transactions`, { params })
  return mapPaginated(res.data)
}

export async function getAdminUserDeposits(
  ulid: string,
  params: { page?: number; per_page?: number } = {},
): Promise<PaginatedData<AdminDeposit>> {
  const res = await adminClient.get<LaravelPaginatedResponse<AdminDeposit>>(`/users/${ulid}/deposits`, { params })
  return mapPaginated(res.data)
}
