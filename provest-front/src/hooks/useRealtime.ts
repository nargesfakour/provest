import { useEffect } from 'react'
import Pusher from 'pusher-js'
import Echo from 'laravel-echo'
import { useQueryClient } from '@tanstack/react-query'
import { useAuthStore } from '@/stores/authStore'

declare global {
  interface Window {
    Pusher: typeof Pusher
  }
}
window.Pusher = Pusher

// eslint-disable-next-line @typescript-eslint/no-explicit-any
let echoInstance: any = null

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function getEcho(): any {
  if (!echoInstance) {
    try {
      echoInstance = new (Echo as any)({
        broadcaster: 'pusher',
        key: (import.meta.env.VITE_PUSHER_APP_KEY as string) || 'local',
        cluster: 'mt1',
        wsHost: (import.meta.env.VITE_WS_HOST as string) || 'provest-api.test',
        wsPort: parseInt((import.meta.env.VITE_WS_PORT as string) || '6001'),
        wssPort: parseInt((import.meta.env.VITE_WS_PORT as string) || '6001'),
        forceTLS: false,
        enabledTransports: ['ws'],
        disableStats: true,
        authEndpoint: `${(import.meta.env.VITE_API_BASE as string) || 'http://provest-api.test/api/v1'}/broadcasting/auth`,
        auth: {
          headers: {
            Authorization: `Bearer ${useAuthStore.getState().token ?? ''}`,
          },
        },
      })
    } catch (err) {
      console.warn('[Echo] WebSocket init failed:', err)
      return null
    }
  }
  return echoInstance
}

/**
 * Subscribe to a public event channel for live order book + trade updates.
 * Invalidates TanStack Query caches so subscribed components re-fetch automatically.
 */
export function useEventRealtime(eventUlid: string | null) {
  const qc = useQueryClient()

  useEffect(() => {
    if (!eventUlid) return
    const echo = getEcho()
    if (!echo) return

    const channel = echo.channel(`event.${eventUlid}`)
    channel
      .listen('.orderbook.updated', () => {
        qc.invalidateQueries({ queryKey: ['orderbook', eventUlid] })
      })
      .listen('.order.matched', () => {
        qc.invalidateQueries({ queryKey: ['trades', eventUlid] })
      })

    return () => {
      echo.leaveChannel(`event.${eventUlid}`)
    }
  }, [eventUlid, qc])
}

export function useUserRealtime() {
  const qc = useQueryClient()
  const userId = useAuthStore((s) => s.user?.ulid)

  useEffect(() => {
    if (!userId) return
    const echo = getEcho()
    if (!echo) return

    const channel = echo.private(`user.${userId}`)
    channel
      .listen('.position.changed', () => {
        qc.invalidateQueries({ queryKey: ['positions'] })
      })
      .listen('.balance.updated', () => {
        qc.invalidateQueries({ queryKey: ['me'] })
      })

    return () => {
      echo.leaveChannel(`private-user.${userId}`)
    }
  }, [userId, qc])
}
