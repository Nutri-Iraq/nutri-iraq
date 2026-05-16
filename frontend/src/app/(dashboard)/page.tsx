'use client'
// src/app/(dashboard)/page.tsx  ← لوحة التحكم
import { useDashboard, useWeightLostChart, useExpiring } from '@/hooks'
import { useNotifStore } from '@/store'
import {
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip,
  LineChart, Line, ResponsiveContainer, Legend,
} from 'recharts'
import { Users, TrendingDown, Activity, AlertTriangle } from 'lucide-react'
import { formatIQD } from '@/lib/utils'

export default function DashboardPage() {
  const { data: stats, isLoading } = useDashboard()
  const { data: chart } = useWeightLostChart('month')
  const { data: expiring } = useExpiring(3)
  const { notifications } = useNotifStore()

  if (isLoading) return <PageLoader />

  const chartData = chart
    ? chart.labels.map((label: string, i: number) => ({ label, value: chart.values[i] }))
    : []

  return (
    <div className="p-5">
      {/* Stats */}
      <div className="grid grid-cols-4 gap-3 mb-5">
        <StatCard
          label="إجمالي المشتركين"
          value={stats?.total_members ?? 0}
          sub={`+${stats?.new_members_this_month} هذا الشهر`}
          icon={<Users size={15} />}
          color="green"
        />
        <StatCard
          label="وزن مفقود (كغ)"
          value={stats?.total_weight_lost_kg?.toFixed(0) ?? 0}
          sub="+9% عن الشهر"
          icon={<TrendingDown size={15} />}
          color="green"
        />
        <StatCard
          label="نشطون"
          value={stats?.active_members ?? 0}
          sub={`${Math.round(((stats?.active_members ?? 0) / (stats?.total_members ?? 1)) * 100)}% من الإجمالي`}
          icon={<Activity size={15} />}
          color="blue"
        />
        <StatCard
          label="تنبيهات"
          value={notifications.filter((n) => !n.is_read).length}
          sub="تحتاج متابعة"
          icon={<AlertTriangle size={15} />}
          color="red"
        />
      </div>

      <div className="grid grid-cols-2 gap-4 mb-4">
        {/* Bar chart */}
        <div className="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl p-4">
          <div className="text-sm font-medium text-gray-800 dark:text-gray-200 mb-4">
            الوزن المفقود أسبوعياً (كغ)
          </div>
          <ResponsiveContainer width="100%" height={160}>
            <BarChart data={chartData}>
              <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
              <XAxis dataKey="label" tick={{ fontSize: 11 }} />
              <YAxis tick={{ fontSize: 11 }} />
              <Tooltip
                contentStyle={{ fontSize: 12, borderRadius: 8 }}
                formatter={(v: number) => [`${v} كغ`, 'الوزن المفقود']}
              />
              <Bar dataKey="value" fill="#1D6B45" radius={[4, 4, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </div>

        {/* Expiring subscriptions */}
        <div className="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl p-4">
          <div className="text-sm font-medium text-gray-800 dark:text-gray-200 mb-3">
            اشتراكات تنتهي قريباً
          </div>
          <div className="space-y-2">
            {expiring?.slice(0, 5).map((sub) => (
              <div key={sub.id} className="flex items-center gap-3 py-1.5 border-b border-gray-50 dark:border-gray-800 last:border-0">
                <div className="w-7 h-7 rounded-full bg-green-50 dark:bg-green-950 flex items-center justify-center text-[10px] font-medium text-green-700 dark:text-green-400">
                  {sub.member?.first_name?.slice(0, 2)}
                </div>
                <div className="flex-1">
                  <div className="text-xs font-medium text-gray-800 dark:text-gray-200">
                    {sub.member?.full_name}
                  </div>
                  <div className="text-[10px] text-gray-400">{sub.member?.governorate}</div>
                </div>
                <span className={`text-[10px] px-2 py-0.5 rounded-full font-medium ${
                  sub.days_remaining === 0
                    ? 'bg-red-100 text-red-700'
                    : 'bg-amber-100 text-amber-700'
                }`}>
                  {sub.days_remaining === 0 ? 'منتهي' : `${sub.days_remaining} أيام`}
                </span>
              </div>
            ))}
            {!expiring?.length && (
              <div className="text-xs text-gray-400 py-4 text-center">لا توجد اشتراكات تنتهي قريباً</div>
            )}
          </div>
        </div>
      </div>

      {/* Revenue */}
      <div className="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl p-4">
        <div className="flex items-center justify-between mb-1">
          <div className="text-sm font-medium text-gray-800 dark:text-gray-200">الإيرادات الشهرية</div>
          <div className="text-sm font-medium text-green-700 dark:text-green-400">
            {formatIQD(stats?.revenue_this_month_iqd ?? 0)} د.ع
          </div>
        </div>
        <div className="text-[11px] text-gray-400">
          معدل الالتزام: {Math.round((stats?.compliance_rate ?? 0) * 100)}%
        </div>
      </div>
    </div>
  )
}

// ── StatCard component ────────────────────────────
function StatCard({
  label, value, sub, icon, color,
}: {
  label: string; value: string | number; sub: string
  icon: React.ReactNode; color: 'green' | 'blue' | 'red'
}) {
  const colors = {
    green: 'bg-green-50 dark:bg-green-950 text-green-700 dark:text-green-400',
    blue: 'bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-400',
    red: 'bg-red-50 dark:bg-red-950 text-red-700 dark:text-red-400',
  }
  return (
    <div className="bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg p-3">
      <div className={`w-7 h-7 rounded-lg flex items-center justify-center mb-2 ${colors[color]}`}>
        {icon}
      </div>
      <div className="text-[11px] text-gray-500 dark:text-gray-400">{label}</div>
      <div className="text-xl font-medium text-gray-800 dark:text-gray-100">{value}</div>
      <div className="text-[10px] text-gray-400 mt-0.5">{sub}</div>
    </div>
  )
}

function PageLoader() {
  return (
    <div className="p-5 space-y-4">
      <div className="grid grid-cols-4 gap-3">
        {Array.from({ length: 4 }).map((_, i) => (
          <div key={i} className="h-24 rounded-lg bg-gray-100 dark:bg-gray-800 animate-pulse" />
        ))}
      </div>
      <div className="grid grid-cols-2 gap-4">
        <div className="h-52 rounded-xl bg-gray-100 dark:bg-gray-800 animate-pulse" />
        <div className="h-52 rounded-xl bg-gray-100 dark:bg-gray-800 animate-pulse" />
      </div>
    </div>
  )
}
