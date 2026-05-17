import { useQuery } from '@tanstack/react-query'
import { membersApi, reportsApi, subscriptionsApi } from '@/lib/api'

export function useMembers(params?: Record<string, unknown>) {
  return useQuery({
    queryKey: ['members', params],
    queryFn: async () => {
      const { data } = await membersApi.list(params)
      return data
    },
  })
}

export function useMember(id: string) {
  return useQuery({
    queryKey: ['member', id],
    queryFn: async () => {
      const { data } = await membersApi.get(id)
      return data
    },
    enabled: !!id,
  })
}

export function useDashboard() {
  return useQuery({
    queryKey: ['dashboard'],
    queryFn: async () => {
      const { data } = await reportsApi.dashboard()
      return data
    },
  })
}

export function useWeightLostChart(period = 'month') {
  return useQuery({
    queryKey: ['weight-chart', period],
    queryFn: async () => {
      const { data } = await reportsApi.weightLost({ period })
      return data
    },
  })
}

export function useExpiring(days = 3) {
  return useQuery({
    queryKey: ['subscriptions-expiring', days],
    queryFn: async () => {
      const { data } = await subscriptionsApi.expiring(days)
      return data
    },
  })
}

export function useMeasurements(memberId: string) {
  return useQuery({
    queryKey: ['measurements', memberId],
    queryFn: async () => {
      const { data } = await membersApi.get(memberId)
      return data
    },
    enabled: !!memberId,
  })
}

export function useMeasurementChart(memberId: string) {
  return useQuery({
    queryKey: ['measurements-chart', memberId],
    queryFn: async () => {
      const { data } = await membersApi.get(memberId)
      return data
    },
    enabled: !!memberId,
  })
}