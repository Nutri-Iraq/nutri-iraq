// src/lib/utils.ts
import { clsx, type ClassValue } from 'clsx'
import { twMerge } from 'tailwind-merge'

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export function formatIQD(amount: number): string {
  return new Intl.NumberFormat('ar-IQ').format(Math.round(amount))
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
  if (bmi < 18.5) return { label: 'نحيف',        color: 'text-blue-600' }
  if (bmi < 25)   return { label: 'طبيعي',        color: 'text-green-600' }
  if (bmi < 30)   return { label: 'زيادة وزن',    color: 'text-amber-600' }
  if (bmi < 35)   return { label: 'سمنة درجة 1',  color: 'text-orange-600' }
  return               { label: 'سمنة مرتفعة',   color: 'text-red-600' }
}

export function goalArabic(goal: string): string {
  const map: Record<string, string> = {
    lose: 'خسارة وزن', gain: 'زيادة وزن',
    muscle: 'بناء عضلات', health: 'تحسين الصحة',
  }
  return map[goal] ?? goal
}

export function packageLabel(pkg: string): string {
  return { '1m': 'شهر واحد', '3m': '3 أشهر', '6m': '6 أشهر' }[pkg] ?? pkg
}

export function packagePrice(pkg: string): number {
  return { '1m': 75_000, '3m': 190_000, '6m': 340_000 }[pkg] ?? 0
}
