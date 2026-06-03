import { useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ArrowRight } from 'lucide-react'

import {
  getAdminUser,
  getAdminUserOrders,
  getAdminUserPositions,
  getAdminUserTransactions,
  getAdminUserDeposits,
} from '@/api/adminUsers'
import Card from '@/components/ui/Card'
import Badge from '@/components/ui/Badge'
import Tabs from '@/components/ui/Tabs'
import Skeleton from '@/components/ui/Skeleton'
import Pagination from '@/components/ui/Pagination'
import { formatDate } from '@/utils/date'
import type { OrderStatus, Side, DepositStatus, WalletTxType, UserStatus } from '@/types/domain'

// ─── Badge maps ───────────────────────────────────────────────────────────────

const orderStatusLabel: Record<OrderStatus, string> = {
  open: 'باز', partial: 'جزئی', filled: 'تکمیل', cancelled: 'لغو',
}

const depositStatusLabel: Record<DepositStatus, string> = {
  confirmed: 'تأیید', pending: 'در انتظار', rejected: 'رد شده',
}

const txTypeLabel: Record<WalletTxType, string> = {
  deposit: 'واریز', withdrawal: 'برداشت', lock: 'قفل', unlock: 'آزاد',
  win: 'برد', loss: 'باخت', fee: 'کارمزد',
}

const txTypeBadge: Record<WalletTxType, 'success' | 'danger' | 'warning' | 'info' | 'default'> = {
  deposit: 'success', win: 'success',
  withdrawal: 'danger', loss: 'danger', fee: 'danger',
  lock: 'warning', unlock: 'info',
}

const userStatusBadge: Record<UserStatus, { variant: 'success' | 'warning' | 'danger' | 'default'; label: string }> = {
  active:               { variant: 'success', label: 'فعال' },
  suspended:            { variant: 'warning', label: 'تعلیق' },
  banned:               { variant: 'danger',  label: 'مسدود' },
  pending_verification: { variant: 'default', label: 'در انتظار تأیید' },
}

// ─── Tab content components ───────────────────────────────────────────────────

function OrdersTab({ ulid }: { ulid: string }) {
  const [page, setPage] = useState(1)
  const { data, isLoading } = useQuery({
    queryKey: ['admin-user-orders', ulid, page],
    queryFn: () => getAdminUserOrders(ulid, { page, per_page: 10 }),
  })
  const orders = data?.items ?? []

  return (
    <div className="overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-[#e8e8e8]">
            {['ایونت', 'جهت', 'قیمت', 'تعداد', 'وضعیت', 'تاریخ'].map((h) => (
              <th key={h} className="px-4 py-3 text-right font-medium text-[#888888]">{h}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {isLoading ? (
            Array.from({ length: 4 }).map((_, i) => (
              <tr key={i} className="border-b border-[#f0f2f5]">
                {Array.from({ length: 6 }).map((__, j) => (
                  <td key={j} className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
                ))}
              </tr>
            ))
          ) : orders.length === 0 ? (
            <tr><td colSpan={6} className="px-4 py-8 text-center text-[#888888]">سفارشی یافت نشد</td></tr>
          ) : orders.map((o) => (
            <tr key={o.ulid} className="border-b border-[#f0f2f5] hover:bg-[#f8f9fa]">
              <td className="px-4 py-3 text-sm text-dark max-w-[180px]">
                <span className="truncate block">{o.event_title}</span>
              </td>
              <td className="px-4 py-3"><Badge variant={o.side as Side}>{o.side.toUpperCase()}</Badge></td>
              <td className="px-4 py-3 font-medium">{o.price}</td>
              <td className="px-4 py-3">{o.quantity}</td>
              <td className="px-4 py-3">
                <Badge variant={o.status === 'open' ? 'open' : o.status === 'filled' ? 'success' : o.status === 'partial' ? 'warning' : 'default'}>
                  {orderStatusLabel[o.status]}
                </Badge>
              </td>
              <td className="px-4 py-3 text-xs text-[#888888]">{formatDate(o.created_at)}</td>
            </tr>
          ))}
        </tbody>
      </table>
      <Pagination page={page} totalPages={data?.last_page ?? 1} onChange={setPage} />
    </div>
  )
}

function PositionsTab({ ulid }: { ulid: string }) {
  const [page, setPage] = useState(1)
  const { data, isLoading } = useQuery({
    queryKey: ['admin-user-positions', ulid, page],
    queryFn: () => getAdminUserPositions(ulid, { page, per_page: 10 }),
  })
  const positions = data?.items ?? []

  return (
    <div className="overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-[#e8e8e8]">
            {['ایونت', 'جهت', 'تعداد', 'قیمت میانگین', 'P&L'].map((h) => (
              <th key={h} className="px-4 py-3 text-right font-medium text-[#888888]">{h}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {isLoading ? (
            Array.from({ length: 4 }).map((_, i) => (
              <tr key={i} className="border-b border-[#f0f2f5]">
                {Array.from({ length: 5 }).map((__, j) => (
                  <td key={j} className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
                ))}
              </tr>
            ))
          ) : positions.length === 0 ? (
            <tr><td colSpan={5} className="px-4 py-8 text-center text-[#888888]">پوزیشنی یافت نشد</td></tr>
          ) : positions.map((p, i) => {
            const pos = parseFloat(p.pnl) >= 0
            return (
              <tr key={i} className="border-b border-[#f0f2f5] hover:bg-[#f8f9fa]">
                <td className="px-4 py-3 text-sm text-dark max-w-[180px]">
                  <span className="truncate block">{p.event_title}</span>
                </td>
                <td className="px-4 py-3"><Badge variant={p.side as Side}>{p.side.toUpperCase()}</Badge></td>
                <td className="px-4 py-3">{p.quantity}</td>
                <td className="px-4 py-3">{p.avg_price}</td>
                <td className={['px-4 py-3 font-medium', pos ? 'text-yes' : 'text-no'].join(' ')}>
                  {pos ? '+' : ''}{p.pnl}
                </td>
              </tr>
            )
          })}
        </tbody>
      </table>
      <Pagination page={page} totalPages={data?.last_page ?? 1} onChange={setPage} />
    </div>
  )
}

function TransactionsTab({ ulid }: { ulid: string }) {
  const [page, setPage] = useState(1)
  const { data, isLoading } = useQuery({
    queryKey: ['admin-user-transactions', ulid, page],
    queryFn: () => getAdminUserTransactions(ulid, { page, per_page: 10 }),
  })
  const txs = data?.items ?? []

  return (
    <div className="overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-[#e8e8e8]">
            {['نوع', 'مبلغ', 'موجودی بعد', 'تاریخ'].map((h) => (
              <th key={h} className="px-4 py-3 text-right font-medium text-[#888888]">{h}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {isLoading ? (
            Array.from({ length: 4 }).map((_, i) => (
              <tr key={i} className="border-b border-[#f0f2f5]">
                {Array.from({ length: 4 }).map((__, j) => (
                  <td key={j} className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
                ))}
              </tr>
            ))
          ) : txs.length === 0 ? (
            <tr><td colSpan={4} className="px-4 py-8 text-center text-[#888888]">تراکنشی یافت نشد</td></tr>
          ) : txs.map((tx) => {
            const isCredit = parseFloat(tx.amount) >= 0
            return (
              <tr key={tx.ulid} className="border-b border-[#f0f2f5] hover:bg-[#f8f9fa]">
                <td className="px-4 py-3">
                  <Badge variant={txTypeBadge[tx.type]}>{txTypeLabel[tx.type]}</Badge>
                </td>
                <td className={['px-4 py-3 font-medium', isCredit ? 'text-yes' : 'text-no'].join(' ')}>
                  {isCredit ? '+' : ''}{tx.amount} USDT
                </td>
                <td className="px-4 py-3 text-[#888888]">{tx.balance_after} USDT</td>
                <td className="px-4 py-3 text-xs text-[#888888]">{formatDate(tx.created_at)}</td>
              </tr>
            )
          })}
        </tbody>
      </table>
      <Pagination page={page} totalPages={data?.last_page ?? 1} onChange={setPage} />
    </div>
  )
}

function DepositsTab({ ulid }: { ulid: string }) {
  const [page, setPage] = useState(1)
  const { data, isLoading } = useQuery({
    queryKey: ['admin-user-deposits', ulid, page],
    queryFn: () => getAdminUserDeposits(ulid, { page, per_page: 10 }),
  })
  const deposits = data?.items ?? []

  return (
    <div className="overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-[#e8e8e8]">
            {['مبلغ', 'شبکه', 'Tx ID', 'وضعیت', 'تاریخ'].map((h) => (
              <th key={h} className="px-4 py-3 text-right font-medium text-[#888888]">{h}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {isLoading ? (
            Array.from({ length: 4 }).map((_, i) => (
              <tr key={i} className="border-b border-[#f0f2f5]">
                {Array.from({ length: 5 }).map((__, j) => (
                  <td key={j} className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
                ))}
              </tr>
            ))
          ) : deposits.length === 0 ? (
            <tr><td colSpan={5} className="px-4 py-8 text-center text-[#888888]">واریزی یافت نشد</td></tr>
          ) : deposits.map((d) => (
            <tr key={d.ulid} className="border-b border-[#f0f2f5] hover:bg-[#f8f9fa]">
              <td className="px-4 py-3 font-medium">{d.amount} <span className="text-xs text-[#888888]">USDT</span></td>
              <td className="px-4 py-3 text-[#888888]">{d.network}</td>
              <td className="px-4 py-3 text-xs text-[#888888] font-mono max-w-[140px]">
                <span className="truncate block">{d.tx_id}</span>
              </td>
              <td className="px-4 py-3">
                <Badge variant={d.status === 'confirmed' ? 'success' : d.status === 'pending' ? 'pending' : 'danger'}>
                  {depositStatusLabel[d.status]}
                </Badge>
              </td>
              <td className="px-4 py-3 text-xs text-[#888888]">{formatDate(d.created_at)}</td>
            </tr>
          ))}
        </tbody>
      </table>
      <Pagination page={page} totalPages={data?.last_page ?? 1} onChange={setPage} />
    </div>
  )
}

// ─── Page ─────────────────────────────────────────────────────────────────────

const TABS = [
  { key: 'orders',       label: 'سفارشات' },
  { key: 'positions',    label: 'پوزیشن‌ها' },
  { key: 'transactions', label: 'تراکنش‌ها' },
  { key: 'deposits',     label: 'واریزها' },
]

export default function AdminUserDetailPage() {
  const { ulid } = useParams<{ ulid: string }>()
  const navigate = useNavigate()
  const [activeTab, setActiveTab] = useState('orders')

  const { data: user, isLoading } = useQuery({
    queryKey: ['admin-user', ulid],
    queryFn: () => getAdminUser(ulid!),
    enabled: !!ulid,
  })

  if (isLoading) {
    return (
      <div className="p-4 md:p-6 space-y-4">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-28 w-full" />
      </div>
    )
  }

  if (!user) return null

  return (
    <div className="p-4 md:p-6 space-y-4 md:space-y-6">

      {/* Back */}
      <div className="flex items-center gap-3">
        <button
          onClick={() => navigate('/admin/users')}
          className="p-2 rounded-lg text-[#888888] hover:bg-[#f0f2f5] transition-colors"
        >
          <ArrowRight size={18} />
        </button>
        <h2 className="text-lg font-bold text-dark">{user.name}</h2>
        <Badge variant={userStatusBadge[user.status].variant}>
          {userStatusBadge[user.status].label}
        </Badge>
      </div>

      {/* User info card */}
      <Card>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
          <div>
            <p className="text-xs text-[#888888] mb-1">نام</p>
            <p className="text-sm font-medium text-dark">{user.name}</p>
          </div>
          <div>
            <p className="text-xs text-[#888888] mb-1">ایمیل</p>
            <p className="text-sm font-medium text-dark">{user.email}</p>
          </div>
          <div>
            <p className="text-xs text-[#888888] mb-1">موجودی</p>
            <p className="text-sm font-bold text-dark">{user.balance} <span className="font-normal text-[#888888]">USDT</span></p>
          </div>
          <div>
            <p className="text-xs text-[#888888] mb-1">عضویت</p>
            <p className="text-sm text-dark">{formatDate(user.created_at)}</p>
          </div>
        </div>
        <div className="mt-4 pt-4 border-t border-[#f0f2f5] flex items-center gap-2">
          <p className="text-xs text-[#888888]">وضعیت حساب:</p>
          <Badge variant={userStatusBadge[user.status].variant}>
            {userStatusBadge[user.status].label}
          </Badge>
        </div>
      </Card>

      {/* Tabs */}
      <div className="bg-white border border-[#e8e8e8] rounded-xl overflow-hidden">
        <div className="px-4 pt-2">
          <Tabs tabs={TABS} active={activeTab} onChange={setActiveTab} />
        </div>
        <div className="p-4">
          {activeTab === 'orders'       && <OrdersTab       ulid={ulid!} />}
          {activeTab === 'positions'    && <PositionsTab    ulid={ulid!} />}
          {activeTab === 'transactions' && <TransactionsTab ulid={ulid!} />}
          {activeTab === 'deposits'     && <DepositsTab     ulid={ulid!} />}
        </div>
      </div>
    </div>
  )
}
