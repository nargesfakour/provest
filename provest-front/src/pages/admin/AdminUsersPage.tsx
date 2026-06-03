import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import { Search, Eye } from 'lucide-react'

import { getAdminUsers } from '@/api/adminUsers'
import Badge from '@/components/ui/Badge'
import Input from '@/components/ui/Input'
import Skeleton from '@/components/ui/Skeleton'
import Pagination from '@/components/ui/Pagination'
import { formatDate } from '@/utils/date'
import type { UserStatus } from '@/types/domain'

const statusOptions = [
  { value: '', label: 'همه کاربران' },
  { value: 'active', label: 'فعال' },
  { value: 'suspended', label: 'تعلیق' },
  { value: 'banned', label: 'مسدود' },
  { value: 'pending_verification', label: 'در انتظار تأیید' },
]

const statusBadge: Record<UserStatus, { variant: 'success' | 'warning' | 'danger' | 'default'; label: string }> = {
  active:               { variant: 'success', label: 'فعال' },
  suspended:            { variant: 'warning', label: 'تعلیق' },
  banned:               { variant: 'danger',  label: 'مسدود' },
  pending_verification: { variant: 'default', label: 'در انتظار تأیید' },
}

export default function AdminUsersPage() {
  const navigate = useNavigate()
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')

  const params = {
    page,
    per_page: 15,
    search: search || undefined,
    status: statusFilter || undefined,
  }

  const { data, isLoading } = useQuery({
    queryKey: ['admin-users', params],
    queryFn: () => getAdminUsers(params),
    staleTime: 30_000,
  })

  const users = data?.items ?? []

  return (
    <div className="p-4 md:p-6 space-y-4">

      {/* Header */}
      <div className="flex items-center justify-between">
        <h2 className="text-base md:text-lg font-bold text-dark">مدیریت کاربران</h2>
      </div>

      {/* Filters */}
      <div className="bg-white border border-[#e8e8e8] rounded-xl p-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-3 sm:gap-4">
        <div className="flex-1">
          <Input
            placeholder="جستجو بر اساس نام یا ایمیل..."
            rightIcon={<Search size={15} />}
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1) }}
          />
        </div>
        <select
          className="rounded-lg border border-[#e8e8e8] bg-white px-3 py-2 text-sm text-dark focus:outline-none focus:ring-2 focus:ring-primary w-full sm:w-auto"
          value={statusFilter}
          onChange={(e) => { setStatusFilter(e.target.value); setPage(1) }}
        >
          {statusOptions.map((o) => (
            <option key={o.value} value={o.value}>{o.label}</option>
          ))}
        </select>
      </div>

      {/* Table */}
      <div className="bg-white border border-[#e8e8e8] rounded-xl overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-[#e8e8e8] bg-[#f8f9fa]">
              <th className="px-4 py-3 text-right font-medium text-[#888888]">نام</th>
              <th className="px-4 py-3 text-right font-medium text-[#888888] hidden md:table-cell">ایمیل</th>
              <th className="px-4 py-3 text-right font-medium text-[#888888] hidden md:table-cell">موجودی (USDT)</th>
              <th className="px-4 py-3 text-right font-medium text-[#888888]">وضعیت</th>
              <th className="px-4 py-3 text-right font-medium text-[#888888] hidden lg:table-cell">عضویت</th>
              <th className="px-4 py-3 text-right font-medium text-[#888888]">عملیات</th>
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              Array.from({ length: 8 }).map((_, i) => (
                <tr key={i} className="border-b border-[#f0f2f5]">
                  <td className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
                  <td className="px-4 py-3 hidden md:table-cell"><Skeleton className="h-4 w-full" /></td>
                  <td className="px-4 py-3 hidden md:table-cell"><Skeleton className="h-4 w-full" /></td>
                  <td className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
                  <td className="px-4 py-3 hidden lg:table-cell"><Skeleton className="h-4 w-full" /></td>
                  <td className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
                </tr>
              ))
            ) : users.length === 0 ? (
              <tr>
                <td colSpan={6} className="px-4 py-12 text-center text-[#888888]">کاربری یافت نشد</td>
              </tr>
            ) : users.map((user) => {
              const sb = statusBadge[user.status]
              return (
                <tr
                  key={user.ulid}
                  onClick={() => navigate(`/admin/users/${user.ulid}`)}
                  className="border-b border-[#f0f2f5] hover:bg-[#f8f9fa] transition-colors cursor-pointer"
                >
                  <td className="px-4 py-3 font-medium text-dark">{user.name}</td>
                  <td className="px-4 py-3 text-[#888888] hidden md:table-cell">{user.email}</td>
                  <td className="px-4 py-3 font-medium hidden md:table-cell">{user.balance} <span className="text-[#888888] text-xs font-normal">USDT</span></td>
                  <td className="px-4 py-3">
                    <Badge variant={sb.variant}>{sb.label}</Badge>
                  </td>
                  <td className="px-4 py-3 text-xs text-[#888888] hidden lg:table-cell">{formatDate(user.created_at)}</td>
                  <td className="px-4 py-3" onClick={(e) => e.stopPropagation()}>
                    <button
                      title="مشاهده"
                      onClick={() => navigate(`/admin/users/${user.ulid}`)}
                      className="p-1.5 rounded-lg text-[#888888] hover:text-primary hover:bg-primary/10 transition-colors"
                    >
                      <Eye size={15} />
                    </button>
                  </td>
                </tr>
              )
            })}
          </tbody>
        </table>
        <Pagination page={page} totalPages={data?.last_page ?? 1} onChange={setPage} />
      </div>
    </div>
  )
}
