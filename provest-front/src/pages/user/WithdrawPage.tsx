import { useState, useEffect } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { AlertCircle, CheckCircle, Info } from 'lucide-react'

import { createWithdrawal, getWithdrawalFee } from '@/api/wallet'
import { useAuthStore } from '@/stores/authStore'
import { formatUsdt } from '@/utils/currency'

import Card from '@/components/ui/Card'
import Button from '@/components/ui/Button'
import Input from '@/components/ui/Input'

// Networks that require a memo/tag field
const MEMO_NETWORKS = new Set(['XLM', 'XRP', 'EOS'])

const NETWORKS = ['TRC20', 'BEP20', 'ERC20'] as const
type Network = (typeof NETWORKS)[number]

function buildSchema(balance: string) {
  const maxBalance = parseFloat(balance || '0')
  return z.object({
    amount: z
      .string()
      .min(1, 'withdraw.errors.amountRequired')
      .refine((v) => parseFloat(v) > 0, { message: 'withdraw.errors.amountMin' })
      .refine((v) => parseFloat(v) <= maxBalance, {
        message: 'withdraw.errors.amountExceedsBalance',
      }),
    network: z.string().min(1),
    destination_address: z.string().min(1, 'withdraw.errors.destinationRequired'),
    memo: z.string().optional(),
  })
}

type FormValues = {
  amount: string
  network: string
  destination_address: string
  memo?: string
}

export default function WithdrawPage() {
  const { t } = useTranslation()
  const queryClient = useQueryClient()
  const user = useAuthStore((s) => s.user)
  const balance = user?.balance ?? '0'

  const [network, setNetwork] = useState<Network>('TRC20')
  const [amountWatch, setAmountWatch] = useState('')

  const schema = buildSchema(balance)

  const {
    register,
    handleSubmit,
    reset,
    watch,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { network: 'TRC20' },
  })

  const watchedAmount = watch('amount')
  useEffect(() => {
    setAmountWatch(watchedAmount ?? '')
  }, [watchedAmount])

  // Fetch fee when amount and network are set
  const { data: feeInfo } = useQuery({
    queryKey: ['withdrawal-fee', network, amountWatch],
    queryFn: () => getWithdrawalFee(network, amountWatch),
    enabled: parseFloat(amountWatch) > 0,
    staleTime: 30_000,
  })

  const { mutate, isPending, isSuccess, error, reset: resetMutation } = useMutation({
    mutationFn: (values: FormValues) =>
      createWithdrawal({
        amount: values.amount,
        network: values.network,
        destination_address: values.destination_address,
        memo: values.memo || undefined,
      }),
    onSuccess: () => {
      reset()
      queryClient.invalidateQueries({ queryKey: ['me'] })
    },
  })

  const needsMemo = MEMO_NETWORKS.has(network)

  return (
    <div className="p-4 md:p-6 w-full max-w-xl space-y-4 md:space-y-6">
      <h1 className="text-lg md:text-xl font-bold text-dark">{t('withdraw.title')}</h1>

      {/* Available balance */}
      <Card>
        <div className="flex items-center justify-between">
          <p className="text-sm text-[#888888]">{t('withdraw.available')}</p>
          <p className="text-xl font-bold font-mono text-dark">
            {formatUsdt(balance)}
          </p>
        </div>
      </Card>

      {/* Withdrawal form */}
      <Card>
        {isSuccess && (
          <div className="flex items-center gap-2 bg-yes-bg border border-yes/30 text-yes rounded-lg px-3 py-2.5 mb-4 text-sm">
            <CheckCircle size={16} className="shrink-0" />
            <span>درخواست برداشت با موفقیت ثبت شد</span>
          </div>
        )}

        {error && (
          <div className="flex items-center gap-2 bg-no-bg border border-no/30 text-no rounded-lg px-3 py-2.5 mb-4 text-sm">
            <AlertCircle size={16} className="shrink-0" />
            <span>{error.message}</span>
          </div>
        )}

        <form
          onSubmit={handleSubmit((v) => { resetMutation(); mutate(v) })}
          noValidate
          className="space-y-4"
        >
          {/* Amount */}
          <Input
            label={t('withdraw.amount')}
            type="number"
            step="0.01"
            min="0"
            placeholder={t('withdraw.amountPlaceholder')}
            error={errors.amount ? t(errors.amount.message ?? '') : undefined}
            {...register('amount')}
          />

          {/* Network selector */}
          <div className="space-y-1.5">
            <label className="text-sm font-medium text-dark">{t('common.network')}</label>
            <div className="flex gap-2">
              {NETWORKS.map((n) => (
                <button
                  key={n}
                  type="button"
                  onClick={() => setNetwork(n)}
                  className={[
                    'flex-1 py-2 rounded-lg border text-sm font-medium transition-colors',
                    network === n
                      ? 'border-primary bg-primary/5 text-primary'
                      : 'border-[#e8e8e8] text-[#888888] hover:border-primary hover:text-primary',
                  ].join(' ')}
                >
                  {n}
                </button>
              ))}
            </div>
            {/* Hidden input to register network in RHF */}
            <input type="hidden" value={network} {...register('network')} />
          </div>

          {/* Destination address */}
          <Input
            label={t('withdraw.destination')}
            placeholder={t('withdraw.destinationPlaceholder')}
            error={errors.destination_address ? t(errors.destination_address.message ?? '') : undefined}
            {...register('destination_address')}
          />

          {/* Memo — only for networks that need it */}
          {needsMemo && (
            <Input
              label={t('withdraw.memo')}
              placeholder={t('withdraw.memoPlaceholder')}
              {...register('memo')}
            />
          )}

          {/* Fee info */}
          {feeInfo && parseFloat(amountWatch) > 0 && (
            <div className="bg-[#f0f2f5] rounded-lg p-3 space-y-2 text-sm">
              <div className="flex justify-between">
                <span className="text-[#888888]">{t('withdraw.fee')}</span>
                <span className="font-medium text-no font-mono">{feeInfo.fee} USDT</span>
              </div>
              <div className="flex justify-between border-t border-[#e8e8e8] pt-2">
                <span className="font-medium text-dark">{t('withdraw.receive')}</span>
                <span className="font-bold text-yes font-mono">{feeInfo.net_amount} USDT</span>
              </div>
            </div>
          )}

          {/* Warning note */}
          <div className="flex items-start gap-2 text-xs text-[#888888] bg-[#f0f2f5] rounded-lg px-3 py-2.5">
            <Info size={14} className="shrink-0 mt-0.5" />
            <span>آدرس مقصد را با دقت وارد کنید. تراکنش‌های اشتباه قابل برگشت نیستند.</span>
          </div>

          <Button type="submit" fullWidth loading={isPending}>
            {t('withdraw.submit')}
          </Button>
        </form>
      </Card>
    </div>
  )
}
