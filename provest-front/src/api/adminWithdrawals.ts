import adminClient from './adminClient'
import { mapPaginated } from '@/types/api'
import type { ApiResponse, LaravelPaginatedResponse, PaginatedData } from '@/types/api'
import type { AdminWithdrawal } from '@/types/domain'

export interface AdminWithdrawalsParams {
  page?: number
  per_page?: number
  status?: string
  from?: string
  to?: string
}

export async function getAdminWithdrawals(params: AdminWithdrawalsParams = {}): Promise<PaginatedData<AdminWithdrawal>> {
  const res = await adminClient.get<LaravelPaginatedResponse<AdminWithdrawal>>('/withdrawals', { params })
  return mapPaginated(res.data)
}

export async function approveWithdrawal(ulid: string): Promise<AdminWithdrawal> {
  const res = await adminClient.post<ApiResponse<AdminWithdrawal>>(`/withdrawals/${ulid}/approve`)
  return res.data.data
}

export async function rejectWithdrawal(ulid: string): Promise<AdminWithdrawal> {
  const res = await adminClient.post<ApiResponse<AdminWithdrawal>>(`/withdrawals/${ulid}/reject`)
  return res.data.data
}
