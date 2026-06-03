import { useState } from 'react'
import { useQuery, useMutation } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Copy, Check, AlertCircle, CheckCircle, QrCode } from 'lucide-react'

import { getDepositInfo, submitDeposit } from '@/api/wallet'

import Card from '@/components/ui/Card'
import Button from '@/components/ui/Button'
import Input from '@/components/ui/Input'
import Skeleton from '@/components/ui/Skeleton'

const NETWORKS = ['TRC20', 'BEP20', 'ERC20'] as const
type Network = (typeof NETWORKS)[number]

const schema = z.object({
  tx_id: z.string().min(1, 'TX ID الزامی است').min(10, 'TX ID معتبر نیست'),
})
type FormValues = z.infer<typeof schema>

export default function DepositPage() {
  const { t } = useTranslation()
  const [network, setNetwork] = useState<Network>('TRC20')
  const [copied, setCopied]   = useState(false)

  const { data: depositInfo, isLoading } = useQuery({
    queryKey: ['deposit-info', network],
    queryFn: () => getDepositInfo(network),
    staleTime: 300_000,
  })

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<FormValues>({ resolver: zodResolver(schema) })

  const { mutate, isPending, isSuccess, error } = useMutation({
    mutationFn: (values: FormValues) =>
      submitDeposit({ tx_id: values.tx_id, network }),
    onSuccess: () => reset(),
  })

  async function copyAddress() {
    if (!depositInfo?.address) return
    await navigator.clipboard.writeText(depositInfo.address)
    setCopied(true)
    setTimeout(() => setCopied(false), 2000)
  }

  return (
    <div className="p-4 md:p-6 w-full max-w-xl space-y-4 md:space-y-6">
      <h1 className="text-lg md:text-xl font-bold text-dark">{t('deposit.title')}</h1>

      {/* Network tabs */}
      <div className="flex gap-1 bg-[#f0f2f5] p-1 rounded-lg w-fit">
        {NETWORKS.map((n) => (
          <button
            key={n}
            onClick={() => setNetwork(n)}
            className={[
              'px-5 py-1.5 rounded-md text-sm font-medium transition-colors',
              network === n
                ? 'bg-white text-dark shadow-sm'
                : 'text-[#888888] hover:text-dark',
            ].join(' ')}
          >
            {n}
          </button>
        ))}
      </div>

      {/* Deposit address card */}
      <Card>
        <p className="text-sm font-medium text-[#888888] mb-3">{t('deposit.address')}</p>

        {isLoading ? (
          <div className="space-y-3">
            <Skeleton className="h-12 w-full" />
            <Skeleton className="h-9 w-32" />
          </div>
        ) : depositInfo ? (
          <>
            {/* Address display */}
            <div className="flex items-center gap-2 bg-[#f0f2f5] rounded-lg px-4 py-3 mb-3">
              <span className="flex-1 font-mono text-sm text-dark break-all select-all">
                {depositInfo.address}
              </span>
            </div>

            {/* Copy button */}
            <Button
              variant="secondary"
              size="sm"
              onClick={copyAddress}
            >
              {copied ? (
                <>
                  <Check size={14} className="text-yes" />
                  {t('common.copied')}
                </>
              ) : (
                <>
                  <Copy size={14} />
                  {t('deposit.copyAddress')}
                </>
              )}
            </Button>

            {/* QR placeholder */}
            <div className="mt-4 flex justify-center">
              <div className="w-36 h-36 border-2 border-dashed border-[#e8e8e8] rounded-xl flex flex-col items-center justify-center gap-2 text-[#bbbbbb]">
                <QrCode size={32} />
                <span className="text-xs">{t('deposit.qrCode')}</span>
              </div>
            </div>
          </>
        ) : (
          <p className="text-sm text-[#888888]">{t('common.error')}</p>
        )}

        {/* Note */}
        <p className="mt-4 text-xs text-[#888888] bg-[#fff3e8] border border-[#f07c30]/30 rounded-lg px-3 py-2">
          ⚠️ {t('deposit.networkNote')} — فقط {network} ارسال کنید
        </p>
      </Card>

      {/* TX ID submission */}
      <Card>
        <h3 className="font-semibold text-dark mb-4">{t('deposit.txId')}</h3>

        {isSuccess && (
          <div className="flex items-center gap-2 bg-yes-bg border border-yes/30 text-yes rounded-lg px-3 py-2.5 mb-4 text-sm">
            <CheckCircle size={16} className="shrink-0" />
            <span>تراکنش با موفقیت ثبت شد. پس از تأیید موجودی شارژ می‌شود.</span>
          </div>
        )}

        {error && (
          <div className="flex items-center gap-2 bg-no-bg border border-no/30 text-no rounded-lg px-3 py-2.5 mb-4 text-sm">
            <AlertCircle size={16} className="shrink-0" />
            <span>{error.message}</span>
          </div>
        )}

        <form onSubmit={handleSubmit((v) => mutate(v))} noValidate className="space-y-4">
          <Input
            label={t('deposit.txId')}
            placeholder={t('deposit.txIdPlaceholder')}
            error={errors.tx_id?.message}
            {...register('tx_id')}
          />

          <p className="text-xs text-[#888888]">
            📌 {t('deposit.note')}
          </p>

          <Button type="submit" fullWidth loading={isPending}>
            {t('deposit.submitTx')}
          </Button>
        </form>
      </Card>
    </div>
  )
}
