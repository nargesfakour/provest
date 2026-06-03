import adminClient from './adminClient'
import { mapPaginated } from '@/types/api'
import type { ApiResponse, LaravelPaginatedResponse, PaginatedData } from '@/types/api'
import type { AdminStats, FeeReport, UserBalance, Trade } from '@/types/domain'

export interface VolumePoint {
  date: string
  volume: string
  fees: string
}

export interface ReportsTradeParams {
  from?: string
  to?: string
  event_ulid?: string
  page?: number
  per_page?: number
}

export interface FeeReportParams {
  from?: string
  to?: string
  page?: number
  per_page?: number
}

export async function getDashboardStats(): Promise<AdminStats> {
  const res = await adminClient.get<ApiResponse<AdminStats>>('/dashboard')
  return res.data.data
}

export async function getVolumeChart(days = 7): Promise<VolumePoint[]> {
  const res = await adminClient.get<ApiResponse<VolumePoint[]>>('/reports/volume', {
    params: { days },
  })
  return res.data.data
}

export async function getFeeReports(params: FeeReportParams = {}): Promise<PaginatedData<FeeReport>> {
  const res = await adminClient.get<LaravelPaginatedResponse<FeeReport>>('/reports/fees', { params })
  return mapPaginated(res.data)
}

export async function getReportsTrades(params: ReportsTradeParams = {}): Promise<PaginatedData<Trade>> {
  const res = await adminClient.get<LaravelPaginatedResponse<Trade>>('/reports/trades', { params })
  return mapPaginated(res.data)
}

export async function getUserBalances(params: { page?: number; per_page?: number } = {}): Promise<PaginatedData<UserBalance>> {
  const res = await adminClient.get<LaravelPaginatedResponse<UserBalance>>('/reports/wallets', { params })
  return mapPaginated(res.data)
}
