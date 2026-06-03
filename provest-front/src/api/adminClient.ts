import axios from 'axios'
import { useAdminAuthStore } from '@/stores/adminAuthStore'

const adminClient = axios.create({
  baseURL: import.meta.env.VITE_ADMIN_API_BASE as string,
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

adminClient.interceptors.request.use((config) => {
  const token = useAdminAuthStore.getState().token
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

adminClient.interceptors.response.use(
  (res) => res,
  (error) => {
    if (error.response?.status === 401) {
      useAdminAuthStore.getState().clearAuth()
      if (!window.location.pathname.startsWith('/admin/login')) {
        window.location.href = '/admin/login'
      }
      return Promise.reject(new Error('احراز هویت ادمین لازم است'))
    }

    const message: string =
      error.response?.data?.message ??
      error.message ??
      'خطایی رخ داد'

    return Promise.reject(new Error(message))
  },
)

export default adminClient
