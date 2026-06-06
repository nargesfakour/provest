import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useTranslation } from 'react-i18next'
import { Plus, Check, X, AlertCircle } from 'lucide-react'

import { getAdmins, createAdmin } from '@/api/adminAdmins'
import Button from '@/components/ui/Button'
import Input from '@/components/ui/Input'
import Modal from '@/components/ui/Modal'
import Skeleton from '@/components/ui/Skeleton'
import Pagination from '@/components/ui/Pagination'
import { formatDate } from '@/utils/date'

const schema = z
  .object({
    name: z.string().min(1, 'auth.errors.nameRequired'),
    email: z.string().min(1, 'auth.errors.emailRequired').email('auth.errors.emailInvalid'),
    password: z
      .string()
      .min(1, 'auth.errors.passwordRequired')
      .min(8, 'auth.errors.passwordMin')
      .regex(/[A-Z]/, 'auth.errors.passwordUppercase')
      .regex(/[a-z]/, 'auth.errors.passwordLowercase')
      .regex(/[0-9]/, 'auth.errors.passwordNumber')
      .regex(/[^A-Za-z0-9]/, 'auth.errors.passwordSpecial'),
    password_confirmation: z.string().min(1, 'auth.errors.passwordRequired'),
  })
  .refine((d) => d.password === d.password_confirmation, {
    message: 'auth.errors.passwordMismatch',
    path: ['password_confirmation'],
  })

type FormValues = z.infer<typeof schema>

function getStrengthColor(score: number): string {
  if (score <= 1) return 'bg-red-500'
  if (score === 2) return 'bg-orange-400'
  if (score === 3) return 'bg-yellow-400'
  if (score === 4) return 'bg-lime-500'
  return 'bg-green-500'
}

function getStrengthLabel(t: (k: string) => string, score: number): string {
  if (score <= 1) return t('auth.passwordStrengthWeak')
  if (score === 2) return t('auth.passwordStrengthFair')
  if (score === 3) return t('auth.passwordStrengthGood')
  if (score === 4) return t('auth.passwordStrengthStrong')
  return t('auth.passwordStrengthVeryStrong')
}

export default function AdminAdminsPage() {
  const { t } = useTranslation()
  const queryClient = useQueryClient()
  const [page, setPage] = useState(1)
  const [showModal, setShowModal] = useState(false)
  const [apiError, setApiError] = useState<string | null>(null)

  const { data, isLoading } = useQuery({
    queryKey: ['admin-admins', page],
    queryFn: () => getAdmins({ page, per_page: 15 }),
    staleTime: 30_000,
  })

  const { mutate, isPending } = useMutation({
    mutationFn: createAdmin,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-admins'] })
      setShowModal(false)
      reset()
      setApiError(null)
    },
    onError: (err: unknown) => {
      const msg =
        (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        'خطایی رخ داد'
      setApiError(msg)
    },
  })

  const {
    register,
    handleSubmit,
    watch,
    reset,
    formState: { errors },
  } = useForm<FormValues>({ resolver: zodResolver(schema) })

  const password = watch('password', '')

  const criteria = {
    length: password.length >= 8,
    uppercase: /[A-Z]/.test(password),
    lowercase: /[a-z]/.test(password),
    number: /[0-9]/.test(password),
    special: /[^A-Za-z0-9]/.test(password),
  }
  const strengthScore = Object.values(criteria).filter(Boolean).length

  function onSubmit(values: FormValues) {
    setApiError(null)
    mutate(values)
  }

  function handleClose() {
    setShowModal(false)
    reset()
    setApiError(null)
  }

  const admins = data?.items ?? []

  return (
    <div className="p-4 md:p-6 space-y-4">

      {/* Header */}
      <div className="flex items-center justify-between">
        <h2 className="text-base md:text-lg font-bold text-dark">{t('admin.admins.title')}</h2>
        <Button onClick={() => setShowModal(true)} className="flex items-center gap-1.5">
          <Plus size={16} />
          {t('admin.admins.addAdmin')}
        </Button>
      </div>

      {/* Table */}
      <div className="bg-white border border-[#e8e8e8] rounded-xl overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-[#e8e8e8] bg-[#f8f9fa]">
              <th className="px-4 py-3 text-right font-medium text-[#888888]">{t('admin.admins.name')}</th>
              <th className="px-4 py-3 text-right font-medium text-[#888888] hidden md:table-cell">{t('admin.admins.email')}</th>
              <th className="px-4 py-3 text-right font-medium text-[#888888] hidden lg:table-cell">{t('admin.admins.joinDate')}</th>
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              Array.from({ length: 8 }).map((_, i) => (
                <tr key={i} className="border-b border-[#f0f2f5]">
                  <td className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
                  <td className="px-4 py-3 hidden md:table-cell"><Skeleton className="h-4 w-full" /></td>
                  <td className="px-4 py-3 hidden lg:table-cell"><Skeleton className="h-4 w-full" /></td>
                </tr>
              ))
            ) : admins.length === 0 ? (
              <tr>
                <td colSpan={3} className="px-4 py-12 text-center text-[#888888]">
                  {t('admin.admins.noAdmins')}
                </td>
              </tr>
            ) : admins.map((admin) => (
              <tr key={admin.ulid} className="border-b border-[#f0f2f5] hover:bg-[#f8f9fa] transition-colors">
                <td className="px-4 py-3 font-medium text-dark">{admin.name}</td>
                <td className="px-4 py-3 text-[#888888] hidden md:table-cell">{admin.email}</td>
                <td className="px-4 py-3 text-xs text-[#888888] hidden lg:table-cell">{formatDate(admin.created_at)}</td>
              </tr>
            ))}
          </tbody>
        </table>
        <Pagination page={page} totalPages={data?.last_page ?? 1} onChange={setPage} />
      </div>

      {/* Create admin modal */}
      <Modal
        isOpen={showModal}
        onClose={handleClose}
        title={t('admin.admins.createTitle')}
      >
        {apiError && (
          <div className="flex items-center gap-2 bg-red-50 border border-red-200 text-red-600 rounded-lg px-3 py-2.5 mb-4 text-sm">
            <AlertCircle size={16} className="shrink-0" />
            <span>{apiError}</span>
          </div>
        )}

        <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-4">
          <Input
            label={t('auth.name')}
            type="text"
            placeholder={t('auth.namePlaceholder')}
            error={errors.name ? t(errors.name.message ?? '') : undefined}
            {...register('name')}
          />

          <Input
            label={t('auth.email')}
            type="email"
            placeholder={t('auth.emailPlaceholder')}
            error={errors.email ? t(errors.email.message ?? '') : undefined}
            {...register('email')}
          />

          <Input
            label={t('auth.password')}
            type="password"
            placeholder={t('auth.passwordPlaceholder')}
            autoComplete="new-password"
            error={errors.password ? t(errors.password.message ?? '') : undefined}
            {...register('password')}
          />

          {password.length > 0 && (
            <div className="space-y-2">
              <div className="flex items-center gap-2">
                <div className="flex gap-1 flex-1">
                  {[1, 2, 3, 4, 5].map((bar) => (
                    <div
                      key={bar}
                      className={[
                        'h-1.5 flex-1 rounded-full transition-colors duration-300',
                        strengthScore >= bar ? getStrengthColor(strengthScore) : 'bg-[#e8e8e8]',
                      ].join(' ')}
                    />
                  ))}
                </div>
                <span className="text-xs text-[#888888] w-16 text-left">
                  {getStrengthLabel(t, strengthScore)}
                </span>
              </div>

              <ul className="space-y-1">
                {(
                  [
                    ['length', 'auth.passwordReqLength'],
                    ['uppercase', 'auth.passwordReqUppercase'],
                    ['lowercase', 'auth.passwordReqLowercase'],
                    ['number', 'auth.passwordReqNumber'],
                    ['special', 'auth.passwordReqSpecial'],
                  ] as const
                ).map(([key, labelKey]) => (
                  <li key={key} className="flex items-center gap-1.5 text-xs">
                    {criteria[key] ? (
                      <Check size={12} className="text-green-500 shrink-0" />
                    ) : (
                      <X size={12} className="text-[#bbbbbb] shrink-0" />
                    )}
                    <span className={criteria[key] ? 'text-green-600' : 'text-[#888888]'}>
                      {t(labelKey)}
                    </span>
                  </li>
                ))}
              </ul>
            </div>
          )}

          <Input
            label={t('auth.confirmPassword')}
            type="password"
            placeholder={t('auth.passwordPlaceholder')}
            autoComplete="new-password"
            error={
              errors.password_confirmation
                ? t(errors.password_confirmation.message ?? '')
                : undefined
            }
            {...register('password_confirmation')}
          />

          <div className="flex gap-3 pt-2">
            <Button type="submit" fullWidth loading={isPending}>
              {t('admin.admins.addAdmin')}
            </Button>
            <Button type="button" variant="ghost" fullWidth onClick={handleClose}>
              {t('common.cancel')}
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  )
}
