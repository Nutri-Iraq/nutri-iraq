'use client'
// src/app/(dashboard)/members/page.tsx
import { useState } from 'react'
import { useMembers, useDeleteMember } from '@/hooks'
import Link from 'next/link'
import { UserPlus, Download, Search, Filter } from 'lucide-react'
import { Member } from '@/types'
import { cn } from '@/lib/utils'

const IRAQI_GOVS = ['بغداد','البصرة','الموصل','أربيل','النجف','كربلاء','الأنبار','ديالى','كركوك','السليمانية','دهوك','صلاح الدين','بابل','واسط','ميسان','ذي قار','المثنى','القادسية']

export default function MembersPage() {
  const [search, setSearch] = useState('')
  const [governorate, setGovernorate] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)

  const { data, isLoading } = useMembers({ search, governorate, status, page })
  const { mutate: deleteMember } = useDeleteMember()

  return (
    <div className="p-5">
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <div>
          <h1 className="text-base font-medium text-gray-800 dark:text-gray-100">إدارة المشتركين</h1>
          <p className="text-[11px] text-gray-400 mt-0.5">
            {data?.meta.total ?? 0} مشترك في جميع المحافظات
          </p>
        </div>
        <div className="flex gap-2">
          <button className="flex items-center gap-1.5 px-3 py-1.5 text-xs border border-gray-200 dark:border-gray-700 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
            <Download size={13} /> تصدير
          </button>
          <Link
            href="/members/new"
            className="flex items-center gap-1.5 px-3 py-1.5 text-xs bg-green-700 hover:bg-green-800 text-white rounded-lg"
          >
            <UserPlus size={13} /> مشترك جديد
          </Link>
        </div>
      </div>

      {/* Filters */}
      <div className="flex gap-2 mb-4">
        <div className="relative flex-1">
          <Search size={13} className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input
            type="text"
            placeholder="بحث بالاسم أو الهاتف..."
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1) }}
            className="w-full pr-8 pl-3 py-2 text-xs border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 focus:outline-none focus:border-green-500"
          />
        </div>
        <select
          value={governorate}
          onChange={(e) => { setGovernorate(e.target.value); setPage(1) }}
          className="px-3 py-2 text-xs border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300"
        >
          <option value="">كل المحافظات</option>
          {IRAQI_GOVS.map((g) => <option key={g} value={g}>{g}</option>)}
        </select>
        <select
          value={status}
          onChange={(e) => { setStatus(e.target.value); setPage(1) }}
          className="px-3 py-2 text-xs border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300"
        >
          <option value="">الكل</option>
          <option value="active">نشط</option>
          <option value="inactive">غير نشط</option>
        </select>
      </div>

      {/* Table */}
      <div className="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-xl overflow-hidden">
        <table className="w-full text-xs" style={{ tableLayout: 'fixed' }}>
          <thead>
            <tr className="border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800">
              <th className="text-right p-3 text-gray-400 font-normal w-[28%]">المشترك</th>
              <th className="text-right p-3 text-gray-400 font-normal w-[12%]">المحافظة</th>
              <th className="text-right p-3 text-gray-400 font-normal w-[11%]">الوزن</th>
              <th className="text-right p-3 text-gray-400 font-normal w-[17%]">التقدم</th>
              <th className="text-right p-3 text-gray-400 font-normal w-[12%]">الحالة</th>
              <th className="w-[10%]"></th>
            </tr>
          </thead>
          <tbody>
            {isLoading
              ? Array.from({ length: 5 }).map((_, i) => (
                  <tr key={i} className="border-b border-gray-50 dark:border-gray-800">
                    {Array.from({ length: 6 }).map((_, j) => (
                      <td key={j} className="p-3"><div className="h-4 bg-gray-100 dark:bg-gray-800 rounded animate-pulse" /></td>
                    ))}
                  </tr>
                ))
              : data?.data.map((member) => (
                  <MemberRow key={member.id} member={member} onDelete={deleteMember} />
                ))
            }
          </tbody>
        </table>

        {/* Pagination */}
        {data && data.meta.last_page > 1 && (
          <div className="flex items-center justify-between p-3 border-t border-gray-100 dark:border-gray-800">
            <span className="text-[11px] text-gray-400">
              صفحة {data.meta.page} من {data.meta.last_page}
            </span>
            <div className="flex gap-1">
              <button
                disabled={page === 1}
                onClick={() => setPage((p) => p - 1)}
                className="px-2.5 py-1 text-[11px] border border-gray-200 dark:border-gray-700 rounded disabled:opacity-40"
              >
                السابق
              </button>
              <button
                disabled={page === data.meta.last_page}
                onClick={() => setPage((p) => p + 1)}
                className="px-2.5 py-1 text-[11px] border border-gray-200 dark:border-gray-700 rounded disabled:opacity-40"
              >
                التالي
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}

function MemberRow({ member, onDelete }: { member: Member; onDelete: (id: string) => void }) {
  const weight = member.latest_measurement?.weight_kg
  const progress = member.weight_goal?.progress_pct ?? 0
  const progressColor = progress >= 80 ? 'bg-green-500' : progress < 10 ? 'bg-red-400' : 'bg-green-700'
  const subStatus = member.active_subscription?.status

  return (
    <tr className="border-b border-gray-50 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
      <td className="p-3">
        <div className="flex items-center gap-2">
          <div className="w-6 h-6 rounded-full bg-green-50 dark:bg-green-950 flex items-center justify-center text-[10px] font-medium text-green-700 dark:text-green-400 flex-shrink-0">
            {member.first_name.slice(0, 2)}
          </div>
          <div>
            <div className="font-medium text-gray-800 dark:text-gray-200">{member.full_name}</div>
            <div className="text-[10px] text-gray-400">{member.phone}</div>
          </div>
        </div>
      </td>
      <td className="p-3 text-gray-500 dark:text-gray-400">{member.governorate}</td>
      <td className="p-3 text-gray-700 dark:text-gray-300">{weight ? `${weight} كغ` : '—'}</td>
      <td className="p-3">
        <div className="text-[10px] text-gray-500 mb-1">{progress.toFixed(0)}%</div>
        <div className="h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full">
          <div className={`h-full rounded-full ${progressColor}`} style={{ width: `${progress}%` }} />
        </div>
      </td>
      <td className="p-3">
        <span className={cn('text-[10px] px-2 py-0.5 rounded-full font-medium', {
          'bg-green-100 dark:bg-green-950 text-green-700 dark:text-green-400': subStatus === 'active',
          'bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-400': subStatus === 'expired',
          'bg-gray-100 dark:bg-gray-800 text-gray-500': !subStatus,
        })}>
          {subStatus === 'active' ? 'نشط' : subStatus === 'expired' ? 'منتهي' : 'بدون اشتراك'}
        </span>
      </td>
      <td className="p-3">
        <Link
          href={`/members/${member.id}`}
          className="text-[11px] px-2.5 py-1 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-300"
        >
          عرض
        </Link>
      </td>
    </tr>
  )
}
