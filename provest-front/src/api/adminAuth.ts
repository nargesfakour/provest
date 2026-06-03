import adminClient from './adminClient'
import type { LoginPayload, AdminAuthResponse } from '@/types/api'
import type { Admin } from '@/types/domain'

export async function adminLogin(payload: LoginPayload): Promise<AdminAuthResponse> {
  const res = await adminClient.post<AdminAuthResponse>('/auth/login', payload)
  if (!res.data.token || !res.data.admin) throw new Error('خطا در ورود')
  return res.data
}

export async function adminLogout(): Promise<void> {
  await adminClient.post('/auth/logout')
}

export async function getAdminMe(): Promise<Admin> {
  const res = await adminClient.get<Admin>('/auth/me')
  if (!res.data) throw new Error('خطا')
  return res.data
}
