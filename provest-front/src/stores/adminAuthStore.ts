import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import type { Admin } from '@/types/domain'

interface AdminAuthState {
  admin: Admin | null
  token: string | null
  setAuth: (admin: Admin, token: string) => void
  clearAuth: () => void
}

export const useAdminAuthStore = create<AdminAuthState>()(
  persist(
    (set) => ({
      admin: null,
      token: null,
      setAuth: (admin, token) => set({ admin, token }),
      clearAuth: () => set({ admin: null, token: null }),
    }),
    {
      name: 'provest-admin-auth',
      partialize: (state) => ({ admin: state.admin, token: state.token }),
    },
  ),
)
