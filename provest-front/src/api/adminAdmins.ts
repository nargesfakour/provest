import adminClient from './adminClient'
import { mapPaginated } from '@/types/api'
import type { LaravelPaginatedResponse, PaginatedData } from '@/types/api'
import type { Admin } from '@/types/domain'

export interface CreateAdminPayload {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export async function getAdmins(params: { page?: number; per_page?: number } = {}): Promise<PaginatedData<Admin>> {
  const res = await adminClient.get<LaravelPaginatedResponse<Admin>>('/admins', { params })
  return mapPaginated(res.data)
}

export async function createAdmin(payload: CreateAdminPayload): Promise<Admin> {
  const res = await adminClient.post<{ data: Admin }>('/admins', payload)
  return res.data.data
}
