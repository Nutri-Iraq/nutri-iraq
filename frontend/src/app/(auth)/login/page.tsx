'use client'
// src/app/(auth)/login/page.tsx
import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { useAuthStore } from '@/store'
import { Salad } from 'lucide-react'

export default function LoginPage() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const { login, isLoading } = useAuthStore()
  const router = useRouter()

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    try {
      await login(email, password)
      router.push('/')
    } catch {
      setError('البريد الإلكتروني أو كلمة المرور غير صحيحة.')
    }
  }

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-950 flex items-center justify-center p-4" dir="rtl">
      <div className="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-8 w-full max-w-sm">
        {/* Logo */}
        <div className="text-center mb-7">
          <div className="w-11 h-11 bg-green-50 dark:bg-green-950 rounded-xl flex items-center justify-center mx-auto mb-3">
            <Salad size={22} className="text-green-700 dark:text-green-400" />
          </div>
          <div className="text-base font-medium text-green-700 dark:text-green-400">نيوتري عراق</div>
          <div className="text-xs text-gray-400 mt-1">نظام إدارة مركز التغذية</div>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="text-xs text-gray-500 dark:text-gray-400 block mb-1.5">
              البريد الإلكتروني
            </label>
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="admin@nutri-iraq.iq"
              required
              className="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:outline-none focus:border-green-500"
            />
          </div>
          <div>
            <label className="text-xs text-gray-500 dark:text-gray-400 block mb-1.5">
              كلمة المرور
            </label>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••"
              required
              className="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:outline-none focus:border-green-500"
            />
          </div>

          {error && (
            <div className="text-xs text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950 px-3 py-2 rounded-lg">
              {error}
            </div>
          )}

          <button
            type="submit"
            disabled={isLoading}
            className="w-full py-2.5 bg-green-700 hover:bg-green-800 disabled:opacity-60 text-white text-sm font-medium rounded-lg transition-colors"
          >
            {isLoading ? 'جارٍ الدخول...' : 'دخول إلى النظام'}
          </button>
        </form>

        <div className="mt-5 pt-4 border-t border-gray-100 dark:border-gray-800 text-center">
          <div className="text-[11px] text-gray-400">
            للمساعدة: <span className="text-green-600">info@nutri-iraq.iq</span>
          </div>
        </div>
      </div>
    </div>
  )
}
