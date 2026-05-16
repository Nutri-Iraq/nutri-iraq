// src/app/layout.tsx
import type { Metadata } from 'next'
import { Providers } from './providers'
import './globals.css'

export const metadata: Metadata = {
  title: 'نيوتري عراق — نظام إدارة التغذية',
  description: 'نظام متكامل لإدارة مراكز التغذية في العراق',
}

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="ar" dir="rtl">
      <body className="font-sans antialiased bg-gray-50 dark:bg-gray-950 text-gray-800 dark:text-gray-100">
        <Providers>{children}</Providers>
      </body>
    </html>
  )
}

// ─────────────────────────────────────────────────
// src/app/providers.tsx
'use client'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { Toaster } from 'react-hot-toast'
import { useState } from 'react'

export function Providers({ children }: { children: React.ReactNode }) {
  const [queryClient] = useState(() => new QueryClient({
    defaultOptions: {
      queries: {
        staleTime: 2 * 60 * 1000, // 2 minutes
        retry: 1,
      },
    },
  }))

  return (
    <QueryClientProvider client={queryClient}>
      {children}
      <Toaster
        position="bottom-left"
        toastOptions={{
          style: { fontSize: 13, fontFamily: 'inherit', direction: 'rtl' },
          success: { style: { background: '#EAF3DE', color: '#3B6D11', border: '0.5px solid #97C459' } },
          error:   { style: { background: '#FCEBEB', color: '#A32D2D', border: '0.5px solid #F09595' } },
        }}
      />
    </QueryClientProvider>
  )
}

// ─────────────────────────────────────────────────
// src/app/(dashboard)/layout.tsx
import { Sidebar } from '@/components/layout/Sidebar'

export default function DashboardLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex h-screen overflow-hidden">
      <Sidebar />
      <main className="flex-1 overflow-y-auto">{children}</main>
    </div>
  )
}

// ─────────────────────────────────────────────────
// src/lib/utils.ts
import { clsx, type ClassValue } from 'clsx'
import { twMerge } from 'tailwind-merge'

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export function formatIQD(amount: number): string {
  return new Intl.NumberFormat('ar-IQ').format(amount)
}

export function formatDate(date: string | null | undefined): string {
  if (!date) return '—'
  return new Intl.DateTimeFormat('ar-IQ', {
    day: 'numeric', month: 'long', year: 'numeric',
  }).format(new Date(date))
}

export function formatShortDate(date: string | null | undefined): string {
  if (!date) return '—'
  return new Intl.DateTimeFormat('ar-IQ', {
    day: '2-digit', month: '2-digit', year: 'numeric',
  }).format(new Date(date))
}

export function calcBMI(weight: number, heightCm: number): number {
  const h = heightCm / 100
  return parseFloat((weight / (h * h)).toFixed(1))
}

export function bmiCategory(bmi: number): { label: string; color: string } {
  if (bmi < 18.5) return { label: 'نحيف', color: 'text-blue-600' }
  if (bmi < 25)   return { label: 'طبيعي', color: 'text-green-600' }
  if (bmi < 30)   return { label: 'زيادة وزن', color: 'text-amber-600' }
  if (bmi < 35)   return { label: 'سمنة درجة 1', color: 'text-orange-600' }
  return { label: 'سمنة مرتفعة', color: 'text-red-600' }
}

export function goalArabic(goal: string): string {
  const map: Record<string, string> = {
    lose: 'خسارة وزن', gain: 'زيادة وزن',
    muscle: 'بناء عضلات', health: 'تحسين الصحة',
  }
  return map[goal] ?? goal
}

export function packageLabel(pkg: string): string {
  const map: Record<string, string> = {
    '1m': 'شهر واحد', '3m': '3 أشهر', '6m': '6 أشهر',
  }
  return map[pkg] ?? pkg
}

export function packagePrice(pkg: string): number {
  const prices: Record<string, number> = {
    '1m': 75_000, '3m': 190_000, '6m': 340_000,
  }
  return prices[pkg] ?? 0
}
