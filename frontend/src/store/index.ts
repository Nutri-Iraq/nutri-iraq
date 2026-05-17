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
    (set) => ({
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
    { name: 'nutri-auth' }
  )
)

interface NotifState {
  unreadCount: number
  setUnreadCount: (count: number) => void
}

export const useNotifStore = create<NotifState>()((set) => ({
  unreadCount: 0,
  setUnreadCount: (count) => set({ unreadCount: count }),
}))