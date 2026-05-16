'use client'
// src/app/providers.tsx
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { Toaster } from 'react-hot-toast'
import { useState } from 'react'

export function Providers({ children }: { children: React.ReactNode }) {
  const [queryClient] = useState(() => new QueryClient({
    defaultOptions: {
      queries: { staleTime: 2 * 60 * 1000, retry: 1 },
    },
  }))

  return (
    <QueryClientProvider client={queryClient}>
      {children}
      <Toaster
        position="bottom-left"
        toastOptions={{
          style: { fontSize: 13, direction: 'rtl' },
          success: { style: { background: '#EAF3DE', color: '#3B6D11' } },
          error:   { style: { background: '#FCEBEB', color: '#A32D2D' } },
        }}
      />
    </QueryClientProvider>
  )
}
