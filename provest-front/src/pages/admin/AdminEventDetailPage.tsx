import { useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ArrowRight } from 'lucide-react'

import {
  getAdminEvent,
  openEvent,
  closeEvent,
  settleEvent,
  getAdminEventOrderBook,
  getAdminEventOrders,
  getAdminEventTrades,
  getAdminEventPositions,
} from '@/api/adminEvents'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import Card from '@/components/ui/Card'
import Tabs from '@/components/ui/Tabs'
import Skeleton from '@/components/ui/Skeleton'
import Modal from '@/components/ui/Modal'
import Pagination from '@/components/ui/Pagination'
import { formatDate } from '@/utils/date'
import type { EventStatus, Side, OrderStatus } from '@/types/domain'

// ─── Badge maps ───────────────────────────────────────────────────────────────

const statusBadge: Record<EventStatus, { variant: 'open' | 'closed' | 'settled' | 'pending'; label: string }> = {
  Open:    { variant: 'open',    label: 'باز' },
  Closed:  { variant: 'closed',  label: 'بسته' },
  Settled: { variant: 'settled', label: 'تسویه' },
  Pending: { variant: 'pending', label: 'در انتظار' },
}

const orderStatusLabel: Record<OrderStatus, string> = {
  open: 'باز', partial: 'جزئی', filled: 'تکمیل', cancelled: 'لغو',
}

const orderStatusVariant: Record<OrderStatus, 'open' | 'warning' | 'success' | 'default'> = {
  open: 'open', partial: 'warning', filled: 'success', cancelled: 'default',
}

// ─── Settle modal ─────────────────────────────────────────────────────────────

interface SettleModalProps {
  isOpen: boolean
  onClose: () => void
  onConfirm: (outcome: 'yes' | 'no') => void
  loading: boolean
}

function SettleModal({ isOpen, onClose, onConfirm, loading }: SettleModalProps) {
  const [outcome, setOutcome] = useState<'yes' | 'no' | null>(null)
  return (
    <Modal isOpen={isOpen} onClose={onClose} title="تسویه ایونت" size="sm">
      <p className="text-sm text-[#888888] mb-4">نتیجه نهایی را انتخاب کنید:</p>
      <div className="grid grid-cols-2 gap-3 mb-6">
        {(['yes', 'no'] as const).map((o) => (
          <button
            key={o}
            onClick={() => setOutcome(o)}
            className={[
              'py-3 rounded-xl border-2 font-bold text-lg transition-colors',
              outcome === o
                ? o === 'yes' ? 'border-yes bg-yes-bg text-yes' : 'border-no bg-no-bg text-no'
                : 'border-[#e8e8e8] text-[#888888] hover:border-primary',
            ].join(' ')}
          >
            {o === 'yes' ? 'YES ✓' : 'NO ✗'}
          </button>
        ))}
      </div>
      <div className="flex gap-3 justify-end">
        <Button variant="secondary" onClick={onClose}>انصراف</Button>
        <Button disabled={!outcome} loading={loading} onClick={() => outcome && onConfirm(outcome)}>
          تأیید تسویه
        </Button>
      </div>
    </Modal>
  )
}

// ─── Order Book tab ───────────────────────────────────────────────────────────

function OrderBookTab({ ulid }: { ulid: string }) {
  const { data, isLoading } = useQuery({
    queryKey: ['admin-event-orderbook', ulid],
    queryFn: () => getAdminEventOrderBook(ulid),
    refetchInterval: 10_000,
  })

  if (isLoading) return <Skeleton className="h-48 w-full mt-4" />
  if (!data) return null

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
      {(['yes', 'no'] as const).map((side) => {
        const levels = side === 'yes' ? data.yes : data.no
        const color = side === 'yes' ? 'text-yes' : 'text-no'
        const bg = side === 'yes' ? 'bg-yes-bg' : 'bg-no-bg'
        return (
          <Card key={side} padding={false}>
            <div className={['px-4 py-2.5 rounded-t-xl text-sm font-semibold', bg, color].join(' ')}>
              {side === 'yes' ? 'سفارشات YES' : 'سفارشات NO'}
            </div>
            <div className="p-2">
              {levels.length === 0 ? (
                <p className="text-xs text-[#888888] text-center py-4">سفارشی نیست</p>
              ) : (
                <div className="space-y-0.5">
                  {levels.map((l, i) => (
                    <div key={i} className="flex justify-between text-xs px-2 py-1 rounded hover:bg-[#f0f2f5]">
                      <span className={color}>{l.price}</span>
                      <span className="text-[#888888]">{l.depth}</span>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </Card>
        )
      })}
    </div>
  )
}

// ─── Orders tab ───────────────────────────────────────────────────────────────

function OrdersTab({ ulid }: { ulid: string }) {
  const [page, setPage] = useState(1)
  const { data, isLoading } = useQuery({
    queryKey: ['admin-event-orders', ulid, page],
    queryFn: () => getAdminEventOrders(ulid, { page, per_page: 15 }),
  })

  const orders = data?.items ?? []

  return (
    <div className="mt-4">
      <div className="bg-white border border-[#e8e8e8] rounded-xl overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-[#e8e8e8] bg-[#f8f9fa]">
              {['جهت', 'قیمت', 'تعداد', 'تکمیل', 'باقیمانده', 'وضعیت', 'تاریخ'].map((h) => (
                <th key={h} className="px-4 py-3 text-right font-medium text-[#888888]">{h}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              Array.from({ length: 6 }).map((_, i) => (
                <tr key={i} className="border-b border-[#f0f2f5]">
                  {Array.from({ length: 7 }).map((__, j) => (
                    <td key={j} className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
                  ))}
                </tr>
              ))
            ) : orders.length === 0 ? (
              <tr><td colSpan={7} className="px-4 py-10 text-center text-[#888888]">سفارشی یافت نشد</td></tr>
            ) : orders.map((o) => (
              <tr key={o.ulid} className="border-b border-[#f0f2f5] hover:bg-[#f8f9fa]">
                <td className="px-4 py-3"><Badge variant={o.side as Side}>{o.side.toUpperCase()}</Badge></td>
                <td className="px-4 py-3 font-medium">{o.price}</td>
                <td className="px-4 py-3">{o.quantity}</td>
                <td className="px-4 py-3 text-yes">{o.filled_quantity}</td>
                <td className="px-4 py-3 text-[#888888]">{o.remaining}</td>
                <td className="px-4 py-3">
                  <Badge variant={orderStatusVariant[o.status]}>{orderStatusLabel[o.status]}</Badge>
                </td>
                <td className="px-4 py-3 text-xs text-[#888888]">{formatDate(o.created_at)}</td>
              </tr>
            ))}
          </tbody>
        </table>
        <Pagination page={page} totalPages={data?.last_page ?? 1} onChange={setPage} />
      </div>
    </div>
  )
}

// ─── Trades tab ───────────────────────────────────────────────────────────────

function TradesTab({ ulid }: { ulid: string }) {
  const [page, setPage] = useState(1)
  const { data, isLoading } = useQuery({
    queryKey: ['admin-event-trades', ulid, page],
    queryFn: () => getAdminEventTrades(ulid, { page, per_page: 15 }),
  })

  const trades = data?.items ?? []

  return (
    <div className="mt-4">
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
                  {Array.from({ length: 4 }).map((__, j) => (
                    <td key={j} className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
                  ))}
                </tr>
              ))
            ) : trades.length === 0 ? (
              <tr><td colSpan={4} className="px-4 py-10 text-center text-[#888888]">معامله‌ای یافت نشد</td></tr>
            ) : trades.map((t) => (
              <tr key={t.ulid} className="border-b border-[#f0f2f5] hover:bg-[#f8f9fa]">
                <td className="px-4 py-3 font-medium">{t.price}</td>
                <td className="px-4 py-3">{t.quantity}</td>
                <td className="px-4 py-3">
                  <Badge variant={t.side as Side}>{t.side.toUpperCase()}</Badge>
                </td>
                <td className="px-4 py-3 text-xs text-[#888888]">{formatDate(t.created_at)}</td>
              </tr>
            ))}
          </tbody>
        </table>
        <Pagination page={page} totalPages={data?.last_page ?? 1} onChange={setPage} />
      </div>
    </div>
  )
}

// ─── Positions tab ────────────────────────────────────────────────────────────

function PositionsTab({ ulid }: { ulid: string }) {
  const [page, setPage] = useState(1)
  const { data, isLoading } = useQuery({
    queryKey: ['admin-event-positions', ulid, page],
    queryFn: () => getAdminEventPositions(ulid, { page, per_page: 15 }),
  })

  const positions = data?.items ?? []

  return (
    <div className="mt-4">
      <div className="bg-white border border-[#e8e8e8] rounded-xl overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-[#e8e8e8] bg-[#f8f9fa]">
              {['جهت', 'تعداد', 'قیمت میانگین', 'قیمت فعلی', 'سود/زیان'].map((h) => (
                <th key={h} className="px-4 py-3 text-right font-medium text-[#888888]">{h}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              Array.from({ length: 6 }).map((_, i) => (
                <tr key={i} className="border-b border-[#f0f2f5]">
                  {Array.from({ length: 5 }).map((__, j) => (
                    <td key={j} className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
                  ))}
                </tr>
              ))
            ) : positions.length === 0 ? (
              <tr><td colSpan={5} className="px-4 py-10 text-center text-[#888888]">پوزیشنی یافت نشد</td></tr>
            ) : positions.map((p, i) => {
              const pnlPositive = parseFloat(p.pnl) >= 0
              return (
                <tr key={i} className="border-b border-[#f0f2f5] hover:bg-[#f8f9fa]">
                  <td className="px-4 py-3"><Badge variant={p.side as Side}>{p.side.toUpperCase()}</Badge></td>
                  <td className="px-4 py-3">{p.quantity}</td>
                  <td className="px-4 py-3">{p.avg_price}</td>
                  <td className="px-4 py-3">{p.current_price}</td>
                  <td className={['px-4 py-3 font-medium', pnlPositive ? 'text-yes' : 'text-no'].join(' ')}>
                    {pnlPositive ? '+' : ''}{p.pnl}
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

// ─── Page ─────────────────────────────────────────────────────────────────────

const TABS = [
  { key: 'orderbook', label: 'دفتر سفارشات' },
  { key: 'orders',    label: 'سفارشات' },
  { key: 'trades',    label: 'معاملات' },
  { key: 'positions', label: 'پوزیشن‌ها' },
]

export default function AdminEventDetailPage() {
  const { ulid } = useParams<{ ulid: string }>()
  const navigate = useNavigate()
  const qc = useQueryClient()
  const [activeTab, setActiveTab] = useState('orderbook')
  const [settleOpen, setSettleOpen] = useState(false)

  const { data: event, isLoading } = useQuery({
    queryKey: ['admin-event', ulid],
    queryFn: () => getAdminEvent(ulid!),
    enabled: !!ulid,
  })

  const openMut = useMutation({
    mutationFn: () => openEvent(ulid!),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-event', ulid] }),
  })

  const closeMut = useMutation({
    mutationFn: () => closeEvent(ulid!),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-event', ulid] }),
  })

  const settleMut = useMutation({
    mutationFn: (outcome: 'yes' | 'no') => settleEvent(ulid!, outcome),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin-event', ulid] })
      setSettleOpen(false)
    },
  })

  if (isLoading) {
    return (
      <div className="p-4 md:p-6 space-y-4">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-32 w-full" />
      </div>
    )
  }

  if (!event) return null

  const sb = statusBadge[event.status]

  return (
    <div className="p-4 md:p-6 space-y-4 md:space-y-6">

      {/* ── Back + header ──────────────────────────────────────────── */}
      <div className="flex items-center gap-3">
        <button
          onClick={() => navigate('/admin/events')}
          className="p-2 rounded-lg text-[#888888] hover:bg-[#f0f2f5] transition-colors"
        >
          <ArrowRight size={18} />
        </button>
        <h2 className="text-lg font-bold text-dark flex-1 truncate">{event.title}</h2>
        <Badge variant={sb.variant}>{sb.label}</Badge>
      </div>

      {/* ── Event info card ────────────────────────────────────────── */}
      <Card>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
          <div>
            <p className="text-xs text-[#888888] mb-1">دسته‌بندی</p>
            <p className="text-sm font-medium text-dark">{event.sub_category.name}</p>
            <p className="text-xs text-[#888888]">{event.sub_category.category.name}</p>
          </div>
          <div>
            <p className="text-xs text-[#888888] mb-1">قیمت YES / NO</p>
            <p className="text-sm font-medium">
              <span className="text-yes">{event.yes_initial_price}</span>
              <span className="text-[#bbbbbb] mx-1">/</span>
              <span className="text-no">{event.no_initial_price}</span>
            </p>
          </div>
          <div>
            <p className="text-xs text-[#888888] mb-1">کارمزد</p>
            <p className="text-sm font-medium text-dark">{event.fee_rate}</p>
          </div>
          <div>
            <p className="text-xs text-[#888888] mb-1">پایان</p>
            <p className="text-sm font-medium text-dark">
              {event.ends_at ? formatDate(event.ends_at) : '—'}
            </p>
          </div>
        </div>

        {/* Status actions */}
        <div className="flex items-center gap-3 mt-5 pt-4 border-t border-[#f0f2f5]">
          {event.status === 'Pending' && (
            <Button size="sm" loading={openMut.isPending} onClick={() => openMut.mutate()}>
              باز کردن ایونت
            </Button>
          )}
          {event.status === 'Open' && (
            <Button size="sm" variant="secondary" loading={closeMut.isPending} onClick={() => closeMut.mutate()}>
              بستن ایونت
            </Button>
          )}
          {event.status === 'Closed' && (
            <Button size="sm" onClick={() => setSettleOpen(true)}>
              تسویه ایونت
            </Button>
          )}
          {event.status === 'Settled' && event.outcome && (
            <Badge variant={event.outcome.toLowerCase() as 'yes' | 'no'}>
              نتیجه: {event.outcome}
            </Badge>
          )}
        </div>
      </Card>

      {/* ── Tabs ──────────────────────────────────────────────────── */}
      <div className="bg-white border border-[#e8e8e8] rounded-xl overflow-hidden">
        <div className="px-4 pt-2">
          <Tabs tabs={TABS} active={activeTab} onChange={setActiveTab} />
        </div>
        <div className="p-4">
          {activeTab === 'orderbook' && <OrderBookTab ulid={ulid!} />}
          {activeTab === 'orders'    && <OrdersTab    ulid={ulid!} />}
          {activeTab === 'trades'    && <TradesTab    ulid={ulid!} />}
          {activeTab === 'positions' && <PositionsTab ulid={ulid!} />}
        </div>
      </div>

      <SettleModal
        isOpen={settleOpen}
        onClose={() => setSettleOpen(false)}
        onConfirm={(o) => settleMut.mutate(o)}
        loading={settleMut.isPending}
      />
    </div>
  )
}
