import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { XCircle } from 'lucide-react'

import { getOrders, cancelOrder } from '@/api/orders'
import { formatDate } from '@/utils/date'
import type { Order, OrderStatus } from '@/types/domain'
import type { Column } from '@/components/ui/Table'

import Table from '@/components/ui/Table'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import Modal from '@/components/ui/Modal'
import Pagination from '@/components/ui/Pagination'
import Card from '@/components/ui/Card'

// ─── Status filter config ─────────────────────────────────────────────────────

interface StatusTab {
  key: string
  labelKey: string
}

const STATUS_TABS: StatusTab[] = [
  { key: '',           labelKey: 'common.all'          },
  { key: 'open',       labelKey: 'orders.statuses.open'      },
  { key: 'partial',    labelKey: 'orders.statuses.partial'   },
  { key: 'filled',     labelKey: 'orders.statuses.filled'    },
  { key: 'cancelled',  labelKey: 'orders.statuses.cancelled' },
]

const STATUS_BADGE: Record<OrderStatus, 'open' | 'warning' | 'yes' | 'default'> = {
  open:      'open',
  partial:   'warning',
  filled:    'yes',
  cancelled: 'default',
}

const PER_PAGE = 15

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function OrdersPage() {
  const { t } = useTranslation()
  const queryClient = useQueryClient()

  const [status, setStatus]       = useState('')
  const [page, setPage]           = useState(1)
  const [cancelTarget, setCancelTarget] = useState<Order | null>(null)

  const { data, isLoading } = useQuery({
    queryKey: ['orders', status, page],
    queryFn: () => getOrders({ status: status || undefined, page, per_page: PER_PAGE }),
    staleTime: 30_000,
  })

  const orders     = data?.items     ?? []
  const totalPages = data?.last_page ?? 1

  const cancelMutation = useMutation({
    mutationFn: (ulid: string) => cancelOrder(ulid),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['orders'] })
      queryClient.invalidateQueries({ queryKey: ['me'] })
      setCancelTarget(null)
    },
  })

  function handleStatusChange(s: string) {
    setStatus(s)
    setPage(1)
  }

  const columns: Column<Order>[] = [
    {
      header: t('orders.event'),
      render: (o) => (
        <span className="text-sm font-medium text-dark line-clamp-1 max-w-[180px]">
          {o.event_title}
        </span>
      ),
    },
    {
      header: t('orders.side'),
      render: (o) => (
        <Badge variant={o.side}>
          {o.side === 'yes' ? t('common.yes') : t('common.no')}
        </Badge>
      ),
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
      header: t('orders.filled'),
      key: 'filled_quantity',
      align: 'center',
      className: 'hidden md:table-cell',
    },
    {
      header: t('orders.remaining'),
      key: 'remaining',
      align: 'center',
      className: 'hidden md:table-cell',
    },
    {
      header: t('orders.status'),
      render: (o) => (
        <Badge variant={STATUS_BADGE[o.status]}>
          {t(`orders.statuses.${o.status}`)}
        </Badge>
      ),
      align: 'center',
    },
    {
      header: t('common.date'),
      render: (o) => (
        <span className="text-xs text-[#888888]">{formatDate(o.created_at)}</span>
      ),
      align: 'center',
      className: 'hidden md:table-cell',
    },
    {
      header: t('common.actions'),
      render: (o) =>
        o.status === 'open' || o.status === 'partial' ? (
          <button
            onClick={() => setCancelTarget(o)}
            className="flex items-center gap-1 text-xs text-no hover:text-[#c94444] transition-colors min-h-[44px]"
          >
            <XCircle size={14} />
            <span className="hidden sm:inline">{t('orders.cancel')}</span>
          </button>
        ) : (
          <span className="text-xs text-[#bbbbbb]">—</span>
        ),
      align: 'center',
    },
  ]

  return (
    <div className="p-4 md:p-6 space-y-4">
      <h1 className="text-lg md:text-xl font-bold text-dark">{t('orders.title')}</h1>

      <Card padding={false}>
        {/* Status filter tabs */}
        <div className="flex items-center gap-1 px-4 pt-4 pb-0 border-b border-[#e8e8e8] overflow-x-auto">
          {STATUS_TABS.map((tab) => (
            <button
              key={tab.key}
              onClick={() => handleStatusChange(tab.key)}
              className={[
                'px-4 py-2.5 text-sm font-medium whitespace-nowrap border-b-2 transition-colors -mb-px',
                status === tab.key
                  ? 'border-primary text-primary'
                  : 'border-transparent text-[#888888] hover:text-dark',
              ].join(' ')}
            >
              {t(tab.labelKey)}
            </button>
          ))}
        </div>

        {/* Table */}
        <Table<Order>
          columns={columns}
          data={orders}
          loading={isLoading}
          keyExtractor={(o) => o.ulid}
          emptyText={t('orders.noOrders')}
        />

        {/* Pagination */}
        <Pagination page={page} totalPages={totalPages} onChange={setPage} />
      </Card>

      {/* Cancel confirmation modal */}
      <Modal
        isOpen={!!cancelTarget}
        onClose={() => setCancelTarget(null)}
        title={t('orders.cancel')}
        size="sm"
      >
        <p className="text-sm text-[#888888] mb-2">{t('orders.cancelConfirm')}</p>

        {cancelTarget && (
          <div className="bg-[#f0f2f5] rounded-lg px-3 py-2 mb-4 text-sm space-y-1">
            <p className="font-medium text-dark line-clamp-1">{cancelTarget.event_title}</p>
            <div className="flex gap-3 text-[#888888]">
              <span>
                <Badge variant={cancelTarget.side}>
                  {cancelTarget.side === 'yes' ? t('common.yes') : t('common.no')}
                </Badge>
              </span>
              <span>{t('orders.price')}: {cancelTarget.price}</span>
              <span>{t('orders.quantity')}: {cancelTarget.quantity}</span>
            </div>
          </div>
        )}

        {cancelMutation.error && (
          <p className="text-xs text-no mb-3">{cancelMutation.error.message}</p>
        )}

        <div className="flex gap-3 justify-end">
          <Button
            variant="secondary"
            size="sm"
            onClick={() => setCancelTarget(null)}
            disabled={cancelMutation.isPending}
          >
            {t('common.cancel')}
          </Button>
          <Button
            variant="danger"
            size="sm"
            loading={cancelMutation.isPending}
            onClick={() => cancelTarget && cancelMutation.mutate(cancelTarget.ulid)}
          >
            {t('orders.cancel')}
          </Button>
        </div>
      </Modal>
    </div>
  )
}
