'use client'
// src/app/(dashboard)/members/[id]/page.tsx
import { use } from 'react'
import { useMember, useMeasurements, useMeasurementChart } from '@/hooks'
import Link from 'next/link'
import {
  ArrowRight, Edit, Apple, Scale, Activity,
  Droplets, Footprints, Moon, Phone, MapPin,
} from 'lucide-react'
import {
  LineChart, Line, XAxis, YAxis, CartesianGrid,
  Tooltip, ResponsiveContainer,
} from 'recharts'
import { formatDate } from '@/lib/utils'

export default function MemberProfilePage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params)
  const { data: member, isLoading } = useMember(id)
  const { data: measurements } = useMeasurements(id)
  const { data: chart } = useMeasurementChart(id)

  if (isLoading) return <div className="p-5 text-sm text-gray-400">جارٍ التحميل...</div>
  if (!member) return <div className="p-5 text-sm text-red-500">المشترك غير موجود</div>

  const latest = measurements?.data[0]
  const chartData = chart
    ? chart.labels.map((label: string, i: number) => ({
        label, weight: chart.weight[i], bmi: chart.bmi[i],
      }))
    : []

  const goalMap: Record<string, string> = {
    lose: 'خسارة وزن', gain: 'زيادة وزن', muscle: 'بناء عضلات', health: 'تحسين الصحة',
  }

  return (
    <div className="p-5">
      {/* Breadcrumb + actions */}
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center gap-2">
          <Link href="/members" className="text-[11px] text-gray-400 hover:text-gray-600 flex items-center gap-1">
            <ArrowRight size={12} /> المشتركون
          </Link>
          <span className="text-[11px] text-gray-300">/</span>
          <span className="text-sm font-medium text-gray-800 dark:text-gray-100">{member.full_name}</span>
          <span className={`text-[10px] px-2 py-0.5 rounded-full font-medium ${
            member.active_subscription?.status === 'active'
              ? 'bg-green-100 text-green-700'
              : 'bg-red-100 text-red-700'
          }`}>
            {member.active_subscription?.status === 'active' ? 'نشط' : 'بدون اشتراك'}
          </span>
        </div>
        <div className="flex gap-2">
          <Link href={`/members/${id}/meal-plan`}
            className="flex items-center gap-1.5 px-3 py-1.5 text-xs border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50">
            <Apple size={12} /> الخطة الغذائية
          </Link>
          <button className="flex items-center gap-1.5 px-3 py-1.5 text-xs bg-green-700 hover:bg-green-800 text-white rounded-lg">
            <Edit size={12} /> تعديل
          </button>
        </div>
      </div>

      <div className="grid grid-cols-2 gap-4 mb-4">
        {/* Info */}
        <div className="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl p-4">
          <div className="flex items-center gap-3 mb-4 pb-3 border-b border-gray-100 dark:border-gray-800">
            <div className="w-11 h-11 rounded-full bg-green-50 dark:bg-green-950 flex items-center justify-center text-base font-medium text-green-700 dark:text-green-400">
              {member.first_name.slice(0, 2)}
            </div>
            <div>
              <div className="text-sm font-medium text-gray-800 dark:text-gray-100">{member.full_name}</div>
              <div className="text-[10px] text-gray-400">مشترك منذ {formatDate(member.created_at)}</div>
            </div>
          </div>
          {[
            { icon: <Scale size={12} />, label: 'العمر', value: `${member.age} سنة` },
            { icon: <Phone size={12} />, label: 'الهاتف', value: member.phone },
            { icon: <MapPin size={12} />, label: 'المحافظة', value: member.governorate },
            { icon: <Activity size={12} />, label: 'الهدف', value: goalMap[member.goal] ?? member.goal },
            {
              icon: <Activity size={12} />, label: 'الاشتراك',
              value: member.active_subscription
                ? `${member.active_subscription.package} — ينتهي ${formatDate(member.active_subscription.end_date)}`
                : 'لا يوجد اشتراك نشط',
              valueColor: member.active_subscription ? 'text-green-600' : 'text-red-500',
            },
          ].map(({ icon, label, value, valueColor }) => (
            <div key={label} className="flex justify-between items-center py-1.5 border-b border-gray-50 dark:border-gray-800 last:border-0 text-xs">
              <span className="text-gray-400 flex items-center gap-1">{icon}{label}</span>
              <span className={`font-medium ${valueColor ?? 'text-gray-700 dark:text-gray-200'}`}>{value}</span>
            </div>
          ))}
        </div>

        {/* Measurements */}
        <div className="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl p-4">
          <div className="flex items-center justify-between mb-3">
            <div className="text-xs font-medium text-gray-800 dark:text-gray-100">القياسات الحالية</div>
            <button className="text-[10px] text-green-700 dark:text-green-400 hover:underline">+ تسجيل قياس</button>
          </div>
          <div className="grid grid-cols-3 gap-2">
            {[
              { label: 'الوزن (كغ)', value: latest?.weight_kg ?? '—', change: '-1.4' },
              { label: 'BMI', value: latest?.bmi?.toFixed(1) ?? '—', change: '-0.5' },
              { label: 'الدهون %', value: latest?.body_fat_pct ?? '—', change: '-0.9' },
              { label: 'الخصر (سم)', value: latest?.waist_cm ?? '—', change: '-2' },
              { label: 'العضلات %', value: latest?.muscle_mass_pct ?? '—', change: '+0.3' },
              {
                label: 'الهدف (كغ)',
                value: member.weight_goal?.target_weight_kg ?? '—',
                change: `${(member.weight_goal?.progress_pct ?? 0).toFixed(0)}%`,
              },
            ].map(({ label, value, change }) => (
              <div key={label} className="bg-gray-50 dark:bg-gray-800 rounded-lg p-2 text-center">
                <div className="text-sm font-medium text-gray-800 dark:text-gray-100">{value}</div>
                <div className="text-[10px] text-gray-400 mt-0.5">{label}</div>
                <div className={`text-[10px] mt-0.5 ${
                  change?.startsWith('-') ? 'text-green-600' : change?.startsWith('+') ? 'text-blue-500' : 'text-gray-400'
                }`}>{change}</div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Weight chart */}
      {chartData.length > 0 && (
        <div className="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl p-4">
          <div className="text-xs font-medium text-gray-800 dark:text-gray-100 mb-4">منحنى الوزن</div>
          <ResponsiveContainer width="100%" height={160}>
            <LineChart data={chartData}>
              <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
              <XAxis dataKey="label" tick={{ fontSize: 10 }} />
              <YAxis tick={{ fontSize: 10 }} domain={['auto', 'auto']} />
              <Tooltip contentStyle={{ fontSize: 11, borderRadius: 8 }}
                formatter={(v: number, n: string) => [`${v}`, n === 'weight' ? 'الوزن (كغ)' : 'BMI']} />
              <Line type="monotone" dataKey="weight" stroke="#1D6B45" strokeWidth={2} dot={{ r: 3 }} />
              <Line type="monotone" dataKey="bmi" stroke="#378ADD" strokeWidth={1.5} dot={{ r: 2 }} strokeDasharray="4 2" />
            </LineChart>
          </ResponsiveContainer>
        </div>
      )}
    </div>
  )
}
