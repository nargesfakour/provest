import axios from 'axios'
import { useAuthStore } from '@/stores/authStore'

const client = axios.create({
  baseURL: import.meta.env.VITE_API_BASE as string,
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

client.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

client.interceptors.response.use(
  (res) => res,
  (error) => {
    if (error.response?.status === 401) {
      useAuthStore.getState().clearAuth()
      if (!window.location.pathname.startsWith('/login')) {
        window.location.href = '/login'
      }
      return Promise.reject(new Error('احراز هویت لازم است'))
    }

    // Lift the backend message so UI always gets a readable string
    const message: string =
      error.response?.data?.message ??
      error.message ??
      'خطایی رخ داد'

    return Promise.reject(new Error(message))
  },
)

export default client
