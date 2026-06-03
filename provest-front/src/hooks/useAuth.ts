import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useAuthStore } from '@/stores/authStore'
import { login, register, logout, getMe } from '@/api/auth'
import type { LoginPayload, RegisterPayload } from '@/types/api'

export function useAuth() {
  const { user, token, setAuth, updateUser, clearAuth } = useAuthStore()
  const queryClient = useQueryClient()

  const loginMutation = useMutation({
    mutationFn: (payload: LoginPayload) => login(payload),
    onSuccess: (data) => setAuth(data.user, data.token),
  })

  const registerMutation = useMutation({
    mutationFn: (payload: RegisterPayload) => register(payload),
    onSuccess: (data) => setAuth(data.user, data.token),
  })

  const logoutMutation = useMutation({
    mutationFn: logout,
    onSettled: () => {
      clearAuth()
      queryClient.clear()
    },
  })

  useQuery({
    queryKey: ['me'],
    queryFn: async () => {
      const me = await getMe()
      updateUser(me)
      return me
    },
    enabled: !!token,
    staleTime: 60_000,
  })

  return {
    user,
    token,
    isAuthenticated: !!token,
    login: loginMutation.mutateAsync,
    register: registerMutation.mutateAsync,
    logout: () => logoutMutation.mutate(),
    loginPending: loginMutation.isPending,
    registerPending: registerMutation.isPending,
    loginError: loginMutation.error?.message,
    registerError: registerMutation.error?.message,
  }
}
