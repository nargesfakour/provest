import adminClient from './adminClient'
import { mapPaginated } from '@/types/api'
import type { ApiResponse, LaravelPaginatedResponse, PaginatedData } from '@/types/api'
import type { Deposit } from '@/types/domain'

export interface AdminDepositsParams {
  page?: number
  per_page?: number
  status?: string
  from?: string
  to?: string
}

export async function getAdminDeposits(params: AdminDepositsParams = {}): Promise<PaginatedData<Deposit>> {
  const res = await adminClient.get<LaravelPaginatedResponse<Deposit>>('/deposits', { params })
  return mapPaginated(res.data)
}

export async function confirmDeposit(ulid: string): Promise<Deposit> {
  const res = await adminClient.put<ApiResponse<Deposit>>(`/deposits/${ulid}/confirm`)
  return res.data.data
}

export async function rejectDeposit(ulid: string): Promise<Deposit> {
  const res = await adminClient.post<ApiResponse<Deposit>>(`/deposits/${ulid}/reject`)
  return res.data.data
}
