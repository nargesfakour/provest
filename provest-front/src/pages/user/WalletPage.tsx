import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { ArrowDownLeft, ArrowUpRight, Wallet } from 'lucide-react'

import { getTransactions } from '@/api/wallet'
import { useAuthStore } from '@/stores/authStore'
import { formatUsdt, formatNumber } from '@/utils/currency'
import { formatDateTime } from '@/utils/date'
import type { WalletTransaction, WalletTxType } from '@/types/domain'
import type { Column } from '@/components/ui/Table'

import Card from '@/components/ui/Card'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import Table from '@/components/ui/Table'
import Skeleton from '@/components/ui/Skeleton'

// ─── Badge config per tx type ─────────────────────────────────────────────────

type BadgeVariant = 'yes' | 'no' | 'warning' | 'info' | 'default' | 'open' | 'closed' | 'settled' | 'pending' | 'danger' | 'success'

const TX_BADGE: Record<WalletTxType, BadgeVariant> = {
  deposit:    'yes',
  withdrawal: 'no',
  lock:       'warning',
  unlock:     'info',
  win:        'yes',
  loss:       'no',
  fee:        'default',
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function WalletPage() {
  const { t } = useTranslation()
  const user = useAuthStore((s) => s.user)

  const { data, isLoading } = useQuery({
    queryKey: ['transactions', 1],
    queryFn: () => getTransactions({ page: 1, per_page: 10 }),
    staleTime: 30_000,
  })

  const transactions = data?.items ?? []

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
        <span className="text-xs text-[#888888]">
          {tx.reference_type ?? '—'}
        </span>
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
    <div className="p-4 md:p-6 space-y-4 md:space-y-6">
      <h1 className="text-lg md:text-xl font-bold text-dark">{t('wallet.title')}</h1>

      {/* Balance card */}
      <Card>
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div className="flex items-center gap-4">
            <div className="w-12 h-12 md:w-14 md:h-14 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
              <Wallet size={24} className="text-primary md:w-7 md:h-7" />
            </div>
            <div>
              <p className="text-sm text-[#888888] mb-1">{t('wallet.balance')}</p>
              {user?.balance ? (
                <p className="text-2xl md:text-3xl font-bold font-mono text-dark">
                  {formatUsdt(user.balance)}
                </p>
              ) : (
                <Skeleton className="h-8 w-40 md:w-48" />
              )}
            </div>
          </div>

          <div className="flex gap-3">
            <Link to="/app/deposit" className="flex-1 sm:flex-none">
              <Button variant="primary" size="md" fullWidth>
                <ArrowDownLeft size={16} />
                {t('wallet.deposit')}
              </Button>
            </Link>
            <Link to="/app/withdraw" className="flex-1 sm:flex-none">
              <Button variant="secondary" size="md" fullWidth>
                <ArrowUpRight size={16} />
                {t('wallet.withdraw')}
              </Button>
            </Link>
          </div>
        </div>
      </Card>

      {/* Recent transactions */}
      <Card padding={false}>
        <div className="flex items-center justify-between p-4 border-b border-[#e8e8e8]">
          <h3 className="font-semibold text-dark">{t('wallet.txHistory')}</h3>
          <Link to="/app/transactions" className="text-sm text-primary hover:underline">
            {t('landing.viewAll')}
          </Link>
        </div>
        <Table<WalletTransaction>
          columns={columns}
          data={transactions}
          loading={isLoading}
          keyExtractor={(tx) => tx.ulid}
          emptyText={t('wallet.noTransactions')}
        />
      </Card>
    </div>
  )
}
