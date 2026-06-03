import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import type { User } from '@/types/domain'

interface AuthState {
  user: User | null
  token: string | null
  setAuth: (user: User, token: string) => void
  updateUser: (user: User) => void
  clearAuth: () => void
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      user: null,
      token: null,
      setAuth: (user, token) => set({ user, token }),
      updateUser: (user) => set({ user }),
      clearAuth: () => set({ user: null, token: null }),
    }),
    {
      name: 'provest-auth',
      partialize: (state) => ({ user: state.user, token: state.token }),
    },
  ),
)
