import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'

import { getAdminWithdrawals, approveWithdrawal, rejectWithdrawal } from '@/api/adminWithdrawals'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import Skeleton from '@/components/ui/Skeleton'
import Pagination from '@/components/ui/Pagination'
import { formatDate } from '@/utils/date'
import type { WithdrawalStatus } from '@/types/domain'

const statusOptions = [
  { value: '', label: 'همه وضعیت‌ها' },
  { value: 'pending', label: 'در انتظار' },
  { value: 'processing', label: 'در حال پردازش' },
  { value: 'done', label: 'انجام شده' },
  { value: 'failed', label: 'ناموفق' },
]

const statusBadge: Record<WithdrawalStatus, { variant: 'pending' | 'info' | 'success' | 'danger'; label: string }> = {
  pending:    { variant: 'pending', label: 'در انتظار' },
  processing: { variant: 'info',    label: 'در پردازش' },
  done:       { variant: 'success', label: 'انجام شده' },
  failed:     { variant: 'danger',  label: 'ناموفق' },
}

export default function AdminWithdrawalsPage() {
  const qc = useQueryClient()
  const [page, setPage] = useState(1)
  const [statusFilter, setStatusFilter] = useState('')
  const [from, setFrom] = useState('')
  const [to, setTo] = useState('')
  const [actingUlid, setActingUlid] = useState<string | null>(null)

  const params = {
    page,
    per_page: 15,
    status: statusFilter || undefined,
    from: from || undefined,
    to: to || undefined,
  }

  const { data, isLoading } = useQuery({
    queryKey: ['admin-withdrawals', params],
    queryFn: () => getAdminWithdrawals(params),
    staleTime: 30_000,
  })

  function invalidate() { qc.invalidateQueries({ queryKey: ['admin-withdrawals'] }) }

  const approveMut = useMutation({
    mutationFn: (ulid: string) => approveWithdrawal(ulid),
    onMutate: (ulid) => setActingUlid(ulid),
    onSettled: () => { setActingUlid(null); invalidate() },
  })

  const rejectMut = useMutation({
    mutationFn: (ulid: string) => rejectWithdrawal(ulid),
    onMutate: (ulid) => setActingUlid(ulid),
    onSettled: () => { setActingUlid(null); invalidate() },
  })

  const withdrawals = data?.items ?? []

  return (
    <div className="p-4 md:p-6 space-y-4">
      <h2 className="text-base md:text-lg font-bold text-dark">مدیریت برداشت‌ها</h2>

      {/* Filters */}
      <div className="bg-white border border-[#e8e8e8] rounded-xl p-4 flex flex-wrap items-center gap-3">
        <select
          className="rounded-lg border border-[#e8e8e8] bg-white px-3 py-2 text-sm text-dark focus:outline-none focus:ring-2 focus:ring-primary"
          value={statusFilter}
          onChange={(e) => { setStatusFilter(e.target.value); setPage(1) }}
        >
          {statusOptions.map((o) => (
            <option key={o.value} value={o.value}>{o.label}</option>
          ))}
        </select>
        <div className="flex items-center gap-2">
          <label className="text-sm text-[#888888]">از:</label>
          <input
            type="date"
            className="rounded-lg border border-[#e8e8e8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
            value={from}
            onChange={(e) => { setFrom(e.target.value); setPage(1) }}
          />
        </div>
        <div className="flex items-center gap-2">
          <label className="text-sm text-[#888888]">تا:</label>
          <input
            type="date"
            className="rounded-lg border border-[#e8e8e8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
            value={to}
            onChange={(e) => { setTo(e.target.value); setPage(1) }}
          />
        </div>
      </div>

      {/* Table */}
      <div className="bg-white border border-[#e8e8e8] rounded-xl overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-[#e8e8e8] bg-[#f8f9fa]">
              <th className="px-4 py-3 text-right font-medium text-[#888888]">مبلغ</th>
              <th className="px-4 py-3 text-right font-medium text-[#888888] hidden lg:table-cell">کارمزد</th>
              <th className="px-4 py-3 text-right font-medium text-[#888888] hidden md:table-cell">شبکه</th>
              <th className="px-4 py-3 text-right font-medium text-[#888888] hidden md:table-cell">آدرس مقصد</th>
              <th className="px-4 py-3 text-right font-medium text-[#888888]">وضعیت</th>
              <th className="px-4 py-3 text-right font-medium text-[#888888] hidden sm:table-cell">تاریخ</th>
              <th className="px-4 py-3 text-right font-medium text-[#888888]">عملیات</th>
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              Array.from({ length: 8 }).map((_, i) => (
                <tr key={i} className="border-b border-[#f0f2f5]">
                  <td className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
                  <td className="px-4 py-3 hidden lg:table-cell"><Skeleton className="h-4 w-full" /></td>
                  <td className="px-4 py-3 hidden md:table-cell"><Skeleton className="h-4 w-full" /></td>
                  <td className="px-4 py-3 hidden md:table-cell"><Skeleton className="h-4 w-full" /></td>
                  <td className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
                  <td className="px-4 py-3 hidden sm:table-cell"><Skeleton className="h-4 w-full" /></td>
                  <td className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
                </tr>
              ))
            ) : withdrawals.length === 0 ? (
              <tr>
                <td colSpan={7} className="px-4 py-12 text-center text-[#888888]">برداشتی یافت نشد</td>
              </tr>
            ) : withdrawals.map((w) => (
              <tr key={w.ulid} className="border-b border-[#f0f2f5] hover:bg-[#f8f9fa]">
                <td className="px-4 py-3 font-medium">
                  {w.amount} <span className="text-xs text-[#888888] font-normal">USDT</span>
                </td>
                <td className="px-4 py-3 text-[#888888] hidden lg:table-cell">{w.fee}</td>
                <td className="px-4 py-3 text-[#888888] hidden md:table-cell">{w.network}</td>
                <td className="px-4 py-3 hidden md:table-cell">
                  <span className="font-mono text-xs text-[#888888] max-w-[140px] truncate block">
                    {w.destination_address}
                  </span>
                </td>
                <td className="px-4 py-3">
                  <Badge variant={statusBadge[w.status].variant}>
                    {statusBadge[w.status].label}
                  </Badge>
                </td>
                <td className="px-4 py-3 text-xs text-[#888888] hidden sm:table-cell">{formatDate(w.created_at)}</td>
                <td className="px-4 py-3">
                  {w.status === 'pending' && (
                    <div className="flex items-center gap-2">
                      <Button
                        size="sm"
                        loading={actingUlid === w.ulid && approveMut.isPending}
                        onClick={() => approveMut.mutate(w.ulid)}
                      >
                        تأیید
                      </Button>
                      <Button
                        size="sm"
                        variant="danger"
                        loading={actingUlid === w.ulid && rejectMut.isPending}
                        onClick={() => rejectMut.mutate(w.ulid)}
                      >
                        رد
                      </Button>
                    </div>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        <Pagination page={page} totalPages={data?.last_page ?? 1} onChange={setPage} />
      </div>
    </div>
  )
}
