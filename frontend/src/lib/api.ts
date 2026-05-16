// src/lib/api.ts
import axios, { AxiosError, AxiosInstance } from 'axios'
import { ApiError } from '@/types'

const api: AxiosInstance = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000/api',
  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
  timeout: 15_000,
})

// ── Request interceptor: أضف الـ token تلقائياً
api.interceptors.request.use((config) => {
  if (typeof window !== 'undefined') {
    const token = localStorage.getItem('nutri_token')
    if (token) config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// ── Response interceptor: معالجة الأخطاء
api.interceptors.response.use(
  (res) => res,
  (error: AxiosError<ApiError>) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('nutri_token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api

// ── Auth endpoints ────────────────────────────────
export const authApi = {
  login: (email: string, password: string) =>
    api.post('/auth/login', { email, password }),
  logout: () => api.post('/auth/logout'),
  me: () => api.get('/auth/me'),
  changePassword: (current_password: string, new_password: string, new_password_confirmation: string) =>
    api.put('/auth/change-password', { current_password, new_password, new_password_confirmation }),
}

// ── Members endpoints ─────────────────────────────
export const membersApi = {
  list: (params?: Record<string, unknown>) => api.get('/members', { params }),
  get: (id: string) => api.get(`/members/${id}`),
  create: (data: unknown) => api.post('/members', data),
  update: (id: string, data: unknown) => api.put(`/members/${id}`, data),
  delete: (id: string) => api.delete(`/members/${id}`),
  timeline: (id: string) => api.get(`/members/${id}/timeline`),
}

// ── Subscriptions endpoints ───────────────────────
export const subscriptionsApi = {
  list: (params?: Record<string, unknown>) => api.get('/subscriptions', { params }),
  create: (data: unknown) => api.post('/subscriptions', data),
  renew: (id: string, data: unknown) => api.put(`/subscriptions/${id}/renew`, data),
  cancel: (id: string) => api.put(`/subscriptions/${id}/cancel`),
  expiring: (days = 3) => api.get('/subscriptions/expiring', { params: { days } }),
}

// ── Measurements endpoints ────────────────────────
export const measurementsApi = {
  list: (memberId: string) => api.get(`/members/${memberId}/measurements`),
  create: (memberId: string, data: unknown) => api.post(`/members/${memberId}/measurements`, data),
  chart: (memberId: string) => api.get(`/members/${memberId}/measurements/chart`),
}

// ── Meal Plans endpoints ──────────────────────────
export const mealPlansApi = {
  list: (memberId: string) => api.get(`/members/${memberId}/meal-plans`),
  get: (id: string) => api.get(`/meal-plans/${id}`),
  create: (memberId: string, data: unknown) => api.post(`/members/${memberId}/meal-plans`, data),
  update: (id: string, data: unknown) => api.put(`/meal-plans/${id}`, data),
  pdf: (id: string) => api.get(`/meal-plans/${id}/pdf`, { responseType: 'blob' }),
  sendWhatsapp: (id: string) => api.post(`/meal-plans/${id}/send-whatsapp`),
}

// ── Daily Tracking endpoints ──────────────────────
export const trackingApi = {
  list: (memberId: string, params?: Record<string, unknown>) =>
    api.get(`/members/${memberId}/tracking`, { params }),
  create: (memberId: string, data: unknown) => api.post(`/members/${memberId}/tracking`, data),
  complianceReport: (params?: Record<string, unknown>) =>
    api.get('/tracking/compliance-report', { params }),
}

// ── Notifications endpoints ───────────────────────
export const notificationsApi = {
  list: (params?: Record<string, unknown>) => api.get('/notifications', { params }),
  read: (id: string) => api.put(`/notifications/${id}/read`),
  readAll: () => api.put('/notifications/read-all'),
  delete: (id: string) => api.delete(`/notifications/${id}`),
  sendReminder: (data: unknown) => api.post('/notifications/send-reminder', data),
}

// ── Reports endpoints ─────────────────────────────
export const reportsApi = {
  dashboard: () => api.get('/reports/dashboard'),
  weightLost: (params?: Record<string, unknown>) => api.get('/reports/weight-lost', { params }),
  byGovernorate: () => api.get('/reports/by-governorate'),
  bmiDistribution: () => api.get('/reports/bmi-distribution'),
  export: (params?: Record<string, unknown>) =>
    api.get('/reports/export', { params, responseType: 'blob' }),
}

// ── Users endpoints ───────────────────────────────
export const usersApi = {
  list: () => api.get('/users'),
  create: (data: unknown) => api.post('/users', data),
  update: (id: string, data: unknown) => api.put(`/users/${id}`, data),
  delete: (id: string) => api.delete(`/users/${id}`),
  toggleActive: (id: string) => api.put(`/users/${id}/toggle-active`),
}

// ── Settings endpoints ────────────────────────────
export const settingsApi = {
  get: () => api.get('/settings'),
  update: (data: unknown) => api.put('/settings', data),
}
