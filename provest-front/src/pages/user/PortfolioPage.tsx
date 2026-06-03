import { useQuery } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { TrendingUp, TrendingDown, DollarSign, Trophy } from 'lucide-react'

import { getPositions } from '@/api/positions'
import { getOrders } from '@/api/orders'
import { formatPnl } from '@/utils/currency'
import { formatDate } from '@/utils/date'
import type { Position, Order } from '@/types/domain'
import type { Column } from '@/components/ui/Table'

import Table from '@/components/ui/Table'
import Badge from '@/components/ui/Badge'
import Card, { CardHeader } from '@/components/ui/Card'
import { SkeletonCard } from '@/components/ui/Skeleton'

// ─── Page ────────────────────────────────────────────────────────────────────

export default function PortfolioPage() {
  const { t } = useTranslation()

  const { data: positions = [], isLoading: posLoading } = useQuery({
    queryKey: ['positions'],
    queryFn: getPositions,
    staleTime: 30_000,
  })

  const { data: ordersData, isLoading: ordLoading } = useQuery({
    queryKey: ['orders', 'open'],
    queryFn: () => getOrders({ status: 'open', per_page: 50 }),
    staleTime: 30_000,
  })

  const openOrders = ordersData?.items ?? []

  // Aggregate stats — float arithmetic acceptable for display summaries
  const totalInvested = positions.reduce(
    (s, p) => s + parseFloat(p.avg_price) * parseFloat(p.quantity), 0,
  )
  const currentValue = positions.reduce(
    (s, p) => s + parseFloat(p.current_price) * parseFloat(p.quantity), 0,
  )
  const totalPnl = positions.reduce((s, p) => s + parseFloat(p.pnl), 0)
  const wins = positions.filter((p) => parseFloat(p.pnl) > 0).length
  const winRate = positions.length > 0 ? (wins / positions.length) * 100 : 0

  return (
    <div className="p-4 md:p-6 space-y-4 md:space-y-6">
      <h1 className="text-lg md:text-xl font-bold text-dark">{t('portfolio.title')}</h1>

      {/* Stats row */}
      {posLoading ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {Array.from({ length: 4 }).map((_, i) => <SkeletonCard key={i} />)}
        </div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <StatCard
            label={t('portfolio.totalInvested')}
            value={`${totalInvested.toFixed(2)} USDT`}
            icon={<DollarSign size={20} className="text-primary" />}
            iconBg="bg-primary/10"
          />
          <StatCard
            label={t('portfolio.currentValue')}
            value={`${currentValue.toFixed(2)} USDT`}
            icon={<DollarSign size={20} className="text-[#3366cc]" />}
            iconBg="bg-[#e8f0ff]"
          />
          <StatCard
            label={t('portfolio.totalPnl')}
            value={`${totalPnl >= 0 ? '+' : ''}${totalPnl.toFixed(2)} USDT`}
            valueClass={totalPnl >= 0 ? 'text-yes' : 'text-no'}
            icon={
              totalPnl >= 0
                ? <TrendingUp size={20} className="text-yes" />
                : <TrendingDown size={20} className="text-no" />
            }
            iconBg={totalPnl >= 0 ? 'bg-yes-bg' : 'bg-no-bg'}
          />
          <StatCard
            label={t('portfolio.winRate')}
            value={`${winRate.toFixed(0)}%`}
            valueClass="text-primary"
            icon={<Trophy size={20} className="text-primary" />}
            iconBg="bg-primary/10"
          />
        </div>
      )}

      {/* Positions table */}
      <Card padding={false}>
        <div className="p-4 border-b border-[#e8e8e8]">
          <CardHeader title={t('portfolio.title')} />
        </div>
        <Table<Position>
          columns={positionColumns(t)}
          data={positions}
          loading={posLoading}
          keyExtractor={(p) => `${p.event_ulid}-${p.side}`}
          emptyText={t('portfolio.noPositions')}
        />
      </Card>

      {/* Open orders */}
      <Card padding={false}>
        <div className="p-4 border-b border-[#e8e8e8]">
          <CardHeader title={t('portfolio.openOrders')} />
        </div>
        <Table<Order>
          columns={openOrderColumns(t)}
          data={openOrders}
          loading={ordLoading}
          keyExtractor={(o) => o.ulid}
          emptyText={t('orders.noOrders')}
        />
      </Card>
    </div>
  )
}

// ─── Stat Card ───────────────────────────────────────────────────────────────

function StatCard({
  label,
  value,
  valueClass = 'text-dark',
  icon,
  iconBg,
}: {
  label: string
  value: string
  valueClass?: string
  icon: React.ReactNode
  iconBg: string
}) {
  return (
    <Card>
      <div className="flex items-center justify-between mb-3">
        <p className="text-sm text-[#888888]">{label}</p>
        <div className={['w-9 h-9 rounded-lg flex items-center justify-center', iconBg].join(' ')}>
          {icon}
        </div>
      </div>
      <p className={['text-xl font-bold font-mono', valueClass].join(' ')}>{value}</p>
    </Card>
  )
}

// ─── Table columns ───────────────────────────────────────────────────────────

function positionColumns(t: (k: string) => string): Column<Position>[] {
  return [
    {
      header: t('portfolio.event'),
      render: (p) => (
        <span className="text-sm font-medium text-dark line-clamp-1">{p.event_title}</span>
      ),
    },
    {
      header: t('portfolio.side'),
      render: (p) => <Badge variant={p.side}>{p.side === 'yes' ? t('common.yes') : t('common.no')}</Badge>,
      align: 'center',
    },
    {
      header: t('portfolio.quantity'),
      key: 'quantity',
      align: 'center',
      className: 'hidden sm:table-cell',
    },
    {
      header: t('portfolio.avgPrice'),
      key: 'avg_price',
      align: 'center',
      className: 'hidden md:table-cell',
    },
    {
      header: t('portfolio.currentPrice'),
      key: 'current_price',
      align: 'center',
      className: 'hidden md:table-cell',
    },
    {
      header: t('portfolio.pnl'),
      render: (p) => {
        const { text, positive } = formatPnl(p.pnl)
        return (
          <span className={['font-semibold font-mono text-sm', positive ? 'text-yes' : 'text-no'].join(' ')}>
            {text}
          </span>
        )
      },
      align: 'center',
    },
    {
      header: t('portfolio.status'),
      render: (p) => {
        const pnlNum = parseFloat(p.pnl)
        const variant = pnlNum > 0 ? 'yes' : pnlNum < 0 ? 'no' : 'default'
        const label = pnlNum > 0 ? 'سودده' : pnlNum < 0 ? 'زیان‌ده' : 'خنثی'
        return <Badge variant={variant}>{label}</Badge>
      },
      align: 'center',
      className: 'hidden sm:table-cell',
    },
  ]
}

function openOrderColumns(t: (k: string) => string): Column<Order>[] {
  return [
    {
      header: t('orders.event'),
      render: (o) => <span className="text-sm text-dark">{o.event_title}</span>,
    },
    {
      header: t('orders.side'),
      render: (o) => <Badge variant={o.side}>{o.side === 'yes' ? t('common.yes') : t('common.no')}</Badge>,
      align: 'center',
    },
    {
      header: t('orders.price'),
      key: 'price',
      align: 'center',
      className: 'hidden sm:table-cell',
    },
    {
      header: t('orders.quantity'),
      key: 'quantity',
      align: 'center',
      className: 'hidden sm:table-cell',
    },
    {
      header: t('orders.remaining'),
      key: 'remaining',
      align: 'center',
      className: 'hidden md:table-cell',
    },
    {
      header: t('common.date'),
      render: (o) => <span className="text-xs text-[#888888]">{formatDate(o.created_at)}</span>,
      align: 'center',
      className: 'hidden md:table-cell',
    },
  ]
}
