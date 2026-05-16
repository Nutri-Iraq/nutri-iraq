// src/store/authStore.ts
import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import { AuthUser } from '@/types'
import { authApi } from '@/lib/api'

interface AuthState {
  user: AuthUser | null
  token: string | null
  isLoading: boolean
  login: (email: string, password: string) => Promise<void>
  logout: () => Promise<void>
  setUser: (user: AuthUser) => void
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      isLoading: false,

      login: async (email, password) => {
        set({ isLoading: true })
        try {
          const { data } = await authApi.login(email, password)
          localStorage.setItem('nutri_token', data.token)
          set({ user: data.user, token: data.token })
        } finally {
          set({ isLoading: false })
        }
      },

      logout: async () => {
        try { await authApi.logout() } catch {}
        localStorage.removeItem('nutri_token')
        set({ user: null, token: null })
      },

      setUser: (user) => set({ user }),
    }),
    {
      name: 'nutri-auth',
      partialize: (state) => ({ user: state.user, token: state.token }),
    }
  )
)

// ─────────────────────────────────────────────────
// src/store/notifStore.ts
import { create } from 'zustand'
import { Notification } from '@/types'
import { notificationsApi } from '@/lib/api'

interface NotifState {
  notifications: Notification[]
  unreadCount: number
  isLoading: boolean
  fetch: () => Promise<void>
  markRead: (id: string) => Promise<void>
  markAllRead: () => Promise<void>
  remove: (id: string) => Promise<void>
}

export const useNotifStore = create<NotifState>((set, get) => ({
  notifications: [],
  unreadCount: 0,
  isLoading: false,

  fetch: async () => {
    set({ isLoading: true })
    try {
      const { data } = await notificationsApi.list()
      const notifs: Notification[] = data.data ?? data
      set({
        notifications: notifs,
        unreadCount: notifs.filter((n) => !n.is_read).length,
      })
    } finally {
      set({ isLoading: false })
    }
  },

  markRead: async (id) => {
    await notificationsApi.read(id)
    set((s) => ({
      notifications: s.notifications.map((n) =>
        n.id === id ? { ...n, is_read: true, read_at: new Date().toISOString() } : n
      ),
      unreadCount: Math.max(0, s.unreadCount - 1),
    }))
  },

  markAllRead: async () => {
    await notificationsApi.readAll()
    set((s) => ({
      notifications: s.notifications.map((n) => ({ ...n, is_read: true })),
      unreadCount: 0,
    }))
  },

  remove: async (id) => {
    await notificationsApi.delete(id)
    const notif = get().notifications.find((n) => n.id === id)
    set((s) => ({
      notifications: s.notifications.filter((n) => n.id !== id),
      unreadCount: notif && !notif.is_read ? Math.max(0, s.unreadCount - 1) : s.unreadCount,
    }))
  },
}))
