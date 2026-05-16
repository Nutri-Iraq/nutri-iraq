'use client'
// src/components/layout/Sidebar.tsx
import Link from 'next/link'
import { usePathname } from 'next/navigation'
import { useAuthStore } from '@/store'
import { useNotifStore } from '@/store'
import {
  LayoutDashboard, Users, UserPlus, Apple, LineChart,
  FileText, Bell, Settings, Salad, LogOut,
} from 'lucide-react'
import { cn } from '@/lib/utils'

const navItems = [
  { href: '/', label: 'لوحة التحكم', icon: LayoutDashboard, section: 'main' },
  { href: '/members', label: 'المشتركون', icon: Users, section: 'main' },
  { href: '/members/new', label: 'إضافة مشترك', icon: UserPlus, section: 'manage' },
  { href: '/meal-plans', label: 'الأنظمة الغذائية', icon: Apple, section: 'manage' },
  { href: '/tracking', label: 'المتابعة', icon: LineChart, section: 'manage' },
  { href: '/reports', label: 'التقارير', icon: FileText, section: 'manage' },
  { href: '/notifications', label: 'الإشعارات', icon: Bell, section: 'system' },
  { href: '/settings', label: 'الإعدادات', icon: Settings, section: 'system' },
]

export function Sidebar() {
  const pathname = usePathname()
  const { user, logout } = useAuthStore()
  const { unreadCount } = useNotifStore()

  const sections = [
    { key: 'main', label: 'الرئيسية' },
    { key: 'manage', label: 'الإدارة' },
    { key: 'system', label: 'النظام' },
  ]

  return (
    <aside className="w-48 h-screen bg-white dark:bg-gray-900 border-l border-gray-100 dark:border-gray-800 flex flex-col flex-shrink-0 sticky top-0">
      {/* Logo */}
      <div className="p-4 border-b border-gray-100 dark:border-gray-800">
        <div className="flex items-center gap-2">
          <div className="w-8 h-8 bg-green-50 dark:bg-green-950 rounded-lg flex items-center justify-center">
            <Salad size={16} className="text-green-700 dark:text-green-400" />
          </div>
          <div>
            <div className="text-sm font-medium text-green-700 dark:text-green-400">نيوتري عراق</div>
            <div className="text-[10px] text-gray-400">مركز التغذية</div>
          </div>
        </div>
      </div>

      {/* Nav */}
      <nav className="flex-1 overflow-y-auto py-2">
        {sections.map(({ key, label }) => {
          const items = navItems.filter((i) => i.section === key)
          return (
            <div key={key} className="mb-1">
              <div className="text-[10px] text-gray-400 px-3.5 pt-2 pb-1">{label}</div>
              {items.map(({ href, label: lbl, icon: Icon }) => {
                const active = pathname === href
                const isNotif = href === '/notifications'
                return (
                  <Link
                    key={href}
                    href={href}
                    className={cn(
                      'flex items-center gap-2 px-3.5 py-1.5 text-xs transition-colors',
                      active
                        ? 'text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-950 font-medium'
                        : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800'
                    )}
                  >
                    <Icon size={14} />
                    <span className="flex-1">{lbl}</span>
                    {isNotif && unreadCount > 0 && (
                      <span className="text-[10px] bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-400 px-1.5 py-0.5 rounded-full">
                        {unreadCount}
                      </span>
                    )}
                  </Link>
                )
              })}
            </div>
          )
        })}
      </nav>

      {/* User */}
      <div className="p-3 border-t border-gray-100 dark:border-gray-800">
        <div className="flex items-center gap-2">
          <div className="w-7 h-7 rounded-full bg-green-50 dark:bg-green-950 flex items-center justify-center text-[11px] font-medium text-green-700 dark:text-green-400 flex-shrink-0">
            {user?.name?.slice(0, 2)}
          </div>
          <div className="flex-1 min-w-0">
            <div className="text-[11px] text-gray-800 dark:text-gray-200 truncate">{user?.name}</div>
            <div className="text-[10px] text-gray-400 truncate">{user?.governorate}</div>
          </div>
          <button
            onClick={logout}
            className="text-gray-400 hover:text-red-500 transition-colors"
            aria-label="تسجيل الخروج"
          >
            <LogOut size={13} />
          </button>
        </div>
      </div>
    </aside>
  )
}
