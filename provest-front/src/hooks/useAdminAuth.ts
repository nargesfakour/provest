import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useAdminAuthStore } from '@/stores/adminAuthStore'
import { adminLogin, adminLogout, getAdminMe } from '@/api/adminAuth'
import type { LoginPayload } from '@/types/api'

export function useAdminAuth() {
  const { admin, token, setAuth, clearAuth } = useAdminAuthStore()
  const queryClient = useQueryClient()

  const loginMutation = useMutation({
    mutationFn: (payload: LoginPayload) => adminLogin(payload),
    onSuccess: (data) => setAuth(data.admin, data.token),
  })

  const logoutMutation = useMutation({
    mutationFn: adminLogout,
    onSettled: () => {
      clearAuth()
      queryClient.clear()
    },
  })

  useQuery({
    queryKey: ['admin-me'],
    queryFn: async () => {
      const me = await getAdminMe()
      return me
    },
    enabled: !!token,
    staleTime: 60_000,
  })

  return {
    admin,
    token,
    isAuthenticated: !!token,
    login: loginMutation.mutateAsync,
    logout: () => logoutMutation.mutate(),
    loginPending: loginMutation.isPending,
    loginError: loginMutation.error?.message,
  }
}
