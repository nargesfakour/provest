import client from './client'
import type { LoginPayload, RegisterPayload, AuthResponse } from '@/types/api'
import type { User } from '@/types/domain'

export async function login(payload: LoginPayload): Promise<AuthResponse> {
  const res = await client.post<AuthResponse>('/auth/login', payload)
  if (!res.data.token || !res.data.user) throw new Error('خطا در ورود')
  return res.data
}

export async function register(payload: RegisterPayload): Promise<AuthResponse> {
  const res = await client.post<AuthResponse>('/auth/register', payload)
  if (!res.data.token || !res.data.user) throw new Error('خطا در ثبت‌نام')
  return res.data
}

export async function logout(): Promise<void> {
  await client.post('/auth/logout')
}

export async function getMe(): Promise<User> {
  const res = await client.get<User>('/auth/me')
  if (!res.data) throw new Error('خطا')
  return res.data
}
