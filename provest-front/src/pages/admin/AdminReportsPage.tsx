import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'

import {
  getFeeReports,
  getReportsTrades,
  getUserBalances,
} from '@/api/adminReports'
import { getAdminEvents } from '@/api/adminEvents'
import Tabs from '@/components/ui/Tabs'
import Badge from '@/components/ui/Badge'
import Skeleton from '@/components/ui/Skeleton'
import Pagination from '@/components/ui/Pagination'
import { formatDate } from '@/utils/date'
import type { Side } from '@/types/domain'

// ─── Shared date range filter ─────────────────────────────────────────────────

interface DateRangeProps {
  from: string
  to: string
  onFrom: (v: string) => void
  onTo: (v: string) => void
  extra?: React.ReactNode
}

function DateRange({ from, to, onFrom, onTo, extra }: DateRangeProps) {
  return (
    <div className="bg-[#f8f9fa] border border-[#e8e8e8] rounded-xl p-4 flex items-center gap-4 flex-wrap mb-4">
      <div className="flex items-center gap-2">
        <label className="text-sm text-[#888888]">از:</label>
        <input
          type="date"
          className="rounded-lg border border-[#e8e8e8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
          value={from}
          onChange={(e) => onFrom(e.target.value)}
        />
      </div>
      <div className="flex items-center gap-2">
        <label className="text-sm text-[#888888]">تا:</label>
        <input
          type="date"
          className="rounded-lg border border-[#e8e8e8] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
          value={to}
          onChange={(e) => onTo(e.target.value)}
        />
      </div>
      {extra}
    </div>
  )
}

// ─── Fees tab ─────────────────────────────────────────────────────────────────

function FeesTab() {
  const [page, setPage] = useState(1)
  const [from, setFrom] = useState('')
  const [to, setTo] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['admin-reports-fees', page, from, to],
    queryFn: () =>
      getFeeReports({ page, per_page: 15, from: from || undefined, to: to || undefined }),
    staleTime: 30_000,
  })

  const rows = data?.items ?? []
  const pageTotal = rows.reduce((s, r) => s + parseFloat(r.total_fee), 0).toFixed(4)

  return (
    <>
      <DateRange
        from={from} to={to}
        onFrom={(v) => { setFrom(v); setPage(1) }}
        onTo={(v) => { setTo(v); setPage(1) }}
      />
      <div className="bg-white border border-[#e8e8e8] rounded-xl overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-[#e8e8e8] bg-[#f8f9fa]">
              {['ایونت', 'تعداد معامله', 'مجموع کارمزد (USDT)'].map((h) => (
                <th key={h} className="px-4 py-3 text-right font-medium text-[#888888]">{h}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              Array.from({ length: 6 }).map((_, i) => (
                <tr key={i} className="border-b border-[#f0f2f5]">
                  {[0, 1, 2].map((j) => (
                    <td key={j} className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
                  ))}
                </tr>
              ))
            ) : rows.length === 0 ? (
              <tr>
                <td colSpan={3} className="px-4 py-10 text-center text-[#888888]">
                  داده‌ای برای این بازه موجود نیست
                </td>
              </tr>
            ) : (
              rows.map((r) => (
                <tr key={r.event_ulid} className="border-b border-[#f0f2f5] hover:bg-[#f8f9fa]">
                  <td className="px-4 py-3 font-medium text-dark max-w-[320px]">
                    <span className="truncate block">{r.event_title}</span>
                  </td>
                  <td className="px-4 py-3 text-[#888888]">
                    {r.trade_count.toLocaleString('fa-IR')}
                  </td>
                  <td className="px-4 py-3 font-medium text-yes">{r.total_fee}</td>
                </tr>
              ))
            )}
          </tbody>
          {rows.length > 0 && (
            <tfoot>
              <tr className="border-t-2 border-[#e8e8e8] bg-[#f8f9fa]">
                <td className="px-4 py-3 font-semibold text-dark" colSpan={2}>
                  جمع این صفحه
                </td>
                <td className="px-4 py-3 font-bold text-yes">{pageTotal} USDT</td>
              </tr>
            </tfoot>
          )}
        </table>
        <Pagination page={page} totalPages={data?.last_page ?? 1} onChange={setPage} />
      </div>
    </>
  )
}

// ─── Trades tab ───────────────────────────────────────────────────────────────

function TradesTab() {
  const [page, setPage] = useState(1)
  const [from, setFrom] = useState('')
  const [to, setTo] = useState('')
  const [eventUlid, setEventUlid] = useState('')

  const { data: eventsData } = useQuery({
    queryKey: ['admin-events-select'],
    queryFn: () => getAdminEvents({ per_page: 100 }),
    staleTime: 300_000,
  })

  const { data, isLoading } = useQuery({
    queryKey: ['admin-reports-trades', page, from, to, eventUlid],
    queryFn: () =>
      getReportsTrades({
        page,
        per_page: 15,
        from: from || undefined,
        to: to || undefined,
        event_ulid: eventUlid || undefined,
      }),
    staleTime: 30_000,
  })

  const trades = data?.items ?? []

  const eventSelector = (
    <select
      className="rounded-lg border border-[#e8e8e8] bg-white px-3 py-2 text-sm text-dark focus:outline-none focus:ring-2 focus:ring-primary"
      value={eventUlid}
      onChange={(e) => { setEventUlid(e.target.value); setPage(1) }}
    >
      <option value="">همه ایونت‌ها</option>
      {(eventsData?.items ?? []).map((ev) => (
        <option key={ev.ulid} value={ev.ulid}>{ev.title}</option>
      ))}
    </select>
  )

  return (
    <>
      <DateRange
        from={from} to={to}
        onFrom={(v) => { setFrom(v); setPage(1) }}
        onTo={(v) => { setTo(v); setPage(1) }}
        extra={eventSelector}
      />
      <div className="bg-white border border-[#e8e8e8] rounded-xl overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-[#e8e8e8] bg-[#f8f9fa]">
              {['قیمت', 'تعداد', 'جهت', 'تاریخ'].map((h) => (
                <th key={h} className="px-4 py-3 text-right font-medium text-[#888888]">{h}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              Array.from({ length: 6 }).map((_, i) => (
                <tr key={i} className="border-b border-[#f0f2f5]">
                  {[0, 1, 2, 3].map((j) => (
                    <td key={j} className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
                  ))}
                </tr>
              ))
            ) : trades.length === 0 ? (
              <tr>
                <td colSpan={4} className="px-4 py-10 text-center text-[#888888]">
                  معامله‌ای در این بازه یافت نشد
                </td>
              </tr>
            ) : (
              trades.map((t) => (
                <tr key={t.ulid} className="border-b border-[#f0f2f5] hover:bg-[#f8f9fa]">
                  <td className="px-4 py-3 font-medium">{t.price}</td>
                  <td className="px-4 py-3">{t.quantity}</td>
                  <td className="px-4 py-3">
                    <Badge variant={t.side as Side}>{t.side.toUpperCase()}</Badge>
                  </td>
                  <td className="px-4 py-3 text-xs text-[#888888]">{formatDate(t.created_at)}</td>
                </tr>
              ))
            )}
          </tbody>
        </table>
        <Pagination page={page} totalPages={data?.last_page ?? 1} onChange={setPage} />
      </div>
    </>
  )
}

// ─── Balances tab ─────────────────────────────────────────────────────────────

function BalancesTab() {
  const [page, setPage] = useState(1)

  const { data, isLoading } = useQuery({
    queryKey: ['admin-reports-balances', page],
    queryFn: () => getUserBalances({ page, per_page: 20 }),
    staleTime: 60_000,
  })

  const rows = data?.items ?? []
  const grandTotal = rows.reduce((s, r) => s + parseFloat(r.balance), 0).toFixed(4)

  return (
    <div className="bg-white border border-[#e8e8e8] rounded-xl overflow-hidden">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-[#e8e8e8] bg-[#f8f9fa]">
            {['نام', 'ایمیل', 'موجودی (USDT)'].map((h) => (
              <th key={h} className="px-4 py-3 text-right font-medium text-[#888888]">{h}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {isLoading ? (
            Array.from({ length: 8 }).map((_, i) => (
              <tr key={i} className="border-b border-[#f0f2f5]">
                {[0, 1, 2].map((j) => (
                  <td key={j} className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
                ))}
              </tr>
            ))
          ) : rows.length === 0 ? (
            <tr>
              <td colSpan={3} className="px-4 py-10 text-center text-[#888888]">
                داده‌ای موجود نیست
              </td>
            </tr>
          ) : (
            rows.map((r) => (
              <tr key={r.user_ulid} className="border-b border-[#f0f2f5] hover:bg-[#f8f9fa]">
                <td className="px-4 py-3 font-medium text-dark">{r.user_name}</td>
                <td className="px-4 py-3 text-[#888888]">{r.user_email}</td>
                <td className="px-4 py-3 font-medium">{r.balance}</td>
              </tr>
            ))
          )}
        </tbody>
        {rows.length > 0 && (
          <tfoot>
            <tr className="border-t-2 border-[#e8e8e8] bg-[#f8f9fa]">
              <td className="px-4 py-3 font-semibold text-dark" colSpan={2}>مجموع این صفحه</td>
              <td className="px-4 py-3 font-bold text-dark">{grandTotal} USDT</td>
            </tr>
          </tfoot>
        )}
      </table>
      <Pagination page={page} totalPages={data?.last_page ?? 1} onChange={setPage} />
    </div>
  )
}

// ─── Page ─────────────────────────────────────────────────────────────────────

const TABS = [
  { key: 'fees',     label: 'کارمزدها' },
  { key: 'trades',   label: 'معاملات' },
  { key: 'balances', label: 'موجودی کاربران' },
]

export default function AdminReportsPage() {
  const [activeTab, setActiveTab] = useState('fees')

  return (
    <div className="p-4 md:p-6 space-y-4">
      <h2 className="text-lg font-bold text-dark">گزارش‌ها</h2>

      <div className="bg-white border border-[#e8e8e8] rounded-xl">
        <div className="px-4 pt-2">
          <Tabs tabs={TABS} active={activeTab} onChange={setActiveTab} />
        </div>
        <div className="p-4">
          {activeTab === 'fees'     && <FeesTab />}
          {activeTab === 'trades'   && <TradesTab />}
          {activeTab === 'balances' && <BalancesTab />}
        </div>
      </div>
    </div>
  )
}
