import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'

import { getTransactions } from '@/api/wallet'
import { formatNumber } from '@/utils/currency'
import { formatDateTime } from '@/utils/date'
import type { WalletTransaction, WalletTxType } from '@/types/domain'
import type { Column } from '@/components/ui/Table'

import Card from '@/components/ui/Card'
import Badge from '@/components/ui/Badge'
import Table from '@/components/ui/Table'
import Pagination from '@/components/ui/Pagination'

const TX_TYPES: WalletTxType[] = ['deposit', 'withdrawal', 'lock', 'unlock', 'win', 'loss', 'fee']

type BadgeVariant = 'yes' | 'no' | 'warning' | 'info' | 'default'

const TX_BADGE: Record<WalletTxType, BadgeVariant> = {
  deposit:    'yes',
  withdrawal: 'no',
  lock:       'warning',
  unlock:     'info',
  win:        'yes',
  loss:       'no',
  fee:        'default',
}

const PER_PAGE = 20

export default function TransactionsPage() {
  const { t } = useTranslation()
  const [type, setType] = useState('')
  const [page, setPage] = useState(1)

  const { data, isLoading } = useQuery({
    queryKey: ['transactions', type, page],
    queryFn: () => getTransactions({ type: type || undefined, page, per_page: PER_PAGE }),
    staleTime: 30_000,
  })

  const transactions = data?.items     ?? []
  const totalPages   = data?.last_page ?? 1

  function handleTypeChange(t: string) {
    setType(t)
    setPage(1)
  }

  const columns: Column<WalletTransaction>[] = [
    {
      header: t('wallet.type'),
      render: (tx) => (
        <Badge variant={TX_BADGE[tx.type]}>
          {t(`wallet.types.${tx.type}`)}
        </Badge>
      ),
    },
    {
      header: t('wallet.amount'),
      render: (tx) => {
        const isPositive = !tx.amount.startsWith('-')
        return (
          <span className={['font-mono font-semibold text-sm', isPositive ? 'text-yes' : 'text-no'].join(' ')}>
            {isPositive ? '+' : ''}{formatNumber(tx.amount)} USDT
          </span>
        )
      },
      align: 'center',
    },
    {
      header: t('wallet.balanceAfter'),
      render: (tx) => (
        <span className="font-mono text-sm text-dark">{formatNumber(tx.balance_after)} USDT</span>
      ),
      align: 'center',
      className: 'hidden md:table-cell',
    },
    {
      header: t('wallet.description'),
      render: (tx) => (
        <span className="text-xs text-[#888888]">{tx.reference_type ?? '—'}</span>
      ),
      align: 'center',
      className: 'hidden md:table-cell',
    },
    {
      header: t('common.date'),
      render: (tx) => (
        <span className="text-xs text-[#888888]">{formatDateTime(tx.created_at)}</span>
      ),
      align: 'center',
      className: 'hidden sm:table-cell',
    },
  ]

  return (
    <div className="p-4 md:p-6 space-y-4">
      <h1 className="text-lg md:text-xl font-bold text-dark">{t('transactions.title')}</h1>

      <Card padding={false}>
        {/* Type filter tabs */}
        <div className="flex items-center gap-1 px-4 pt-4 border-b border-[#e8e8e8] overflow-x-auto">
          <TypeTab label={t('common.all')} active={type === ''} onClick={() => handleTypeChange('')} />
          {TX_TYPES.map((txType) => (
            <TypeTab
              key={txType}
              label={t(`wallet.types.${txType}`)}
              active={type === txType}
              onClick={() => handleTypeChange(txType)}
            />
          ))}
        </div>

        <Table<WalletTransaction>
          columns={columns}
          data={transactions}
          loading={isLoading}
          keyExtractor={(tx) => tx.ulid}
          emptyText={t('wallet.noTransactions')}
        />

        <Pagination page={page} totalPages={totalPages} onChange={setPage} />
      </Card>
    </div>
  )
}

function TypeTab({
  label,
  active,
  onClick,
}: {
  label: string
  active: boolean
  onClick: () => void
}) {
  return (
    <button
      onClick={onClick}
      className={[
        'px-4 py-2.5 text-sm font-medium whitespace-nowrap border-b-2 transition-colors -mb-px',
        active
          ? 'border-primary text-primary'
          : 'border-transparent text-[#888888] hover:text-dark',
      ].join(' ')}
    >
      {label}
    </button>
  )
}
