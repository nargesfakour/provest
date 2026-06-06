import { Link, useNavigate } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useTranslation } from 'react-i18next'
import { AlertCircle, Check, X } from 'lucide-react'

import { useAuth } from '@/hooks/useAuth'
import Button from '@/components/ui/Button'
import Input from '@/components/ui/Input'

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

function getStrengthLabel(t: (k: string) => string, score: number): string {
  if (score <= 1) return t('auth.passwordStrengthWeak')
  if (score === 2) return t('auth.passwordStrengthFair')
  if (score === 3) return t('auth.passwordStrengthGood')
  if (score === 4) return t('auth.passwordStrengthStrong')
  return t('auth.passwordStrengthVeryStrong')
}

function getStrengthColor(score: number): string {
  if (score <= 1) return 'bg-red-500'
  if (score === 2) return 'bg-orange-400'
  if (score === 3) return 'bg-yellow-400'
  if (score === 4) return 'bg-lime-500'
  return 'bg-green-500'
}

export default function RegisterPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { register: registerUser, registerPending, registerError } = useAuth()

  const {
    register,
    handleSubmit,
    watch,
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

  async function onSubmit(values: FormValues) {
    try {
      await registerUser(values)
      navigate('/app/events', { replace: true })
    } catch {
      // error displayed via registerError
    }
  }

  return (
    <div className="min-h-screen bg-[#f0f2f5] flex items-center justify-center px-4 py-8">
      <div className="w-full max-w-sm">

        {/* Logo */}
        <div className="flex flex-col items-center mb-8">
          <div className="w-12 h-12 bg-primary rounded-xl flex items-center justify-center mb-3">
            <span className="text-white font-bold text-xl">گ</span>
          </div>
          <h1 className="text-xl font-bold text-dark">{t('auth.registerTitle')}</h1>
        </div>

        {/* Card */}
        <div className="bg-white border border-[#e8e8e8] rounded-xl p-6 shadow-sm">

          {/* API error */}
          {registerError && (
            <div className="flex items-center gap-2 bg-no-bg border border-no/30 text-no rounded-lg px-3 py-2.5 mb-4 text-sm">
              <AlertCircle size={16} className="shrink-0" />
              <span>{registerError}</span>
            </div>
          )}

          <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-4">
            <Input
              label={t('auth.name')}
              type="text"
              placeholder={t('auth.namePlaceholder')}
              autoComplete="name"
              error={errors.name ? t(errors.name.message ?? '') : undefined}
              {...register('name')}
            />

            <Input
              label={t('auth.email')}
              type="email"
              placeholder={t('auth.emailPlaceholder')}
              autoComplete="email"
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
                {/* Strength bars */}
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

                {/* Criteria checklist */}
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

            <Button
              type="submit"
              fullWidth
              loading={registerPending}
              className="mt-2"
            >
              {t('auth.register')}
            </Button>
          </form>
        </div>

        {/* Switch to login */}
        <p className="text-center text-sm text-[#888888] mt-4">
          {t('auth.haveAccount')}{' '}
          <Link to="/login" className="text-primary font-medium hover:underline">
            {t('auth.registerLink')}
          </Link>
        </p>
      </div>
    </div>
  )
}
