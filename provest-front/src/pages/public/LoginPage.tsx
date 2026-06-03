import { Link, useNavigate } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useTranslation } from 'react-i18next'
import { AlertCircle } from 'lucide-react'

import { useAuth } from '@/hooks/useAuth'
import Button from '@/components/ui/Button'
import Input from '@/components/ui/Input'

const schema = z.object({
  email: z.string().min(1, 'auth.errors.emailRequired').email('auth.errors.emailInvalid'),
  password: z.string().min(1, 'auth.errors.passwordRequired').min(8, 'auth.errors.passwordMin'),
})

type FormValues = z.infer<typeof schema>

export default function LoginPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { login, loginPending, loginError } = useAuth()

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<FormValues>({ resolver: zodResolver(schema) })

  async function onSubmit(values: FormValues) {
    try {
      await login(values)
      navigate('/app/events', { replace: true })
    } catch {
      // error displayed via loginError
    }
  }

  return (
    <div className="min-h-screen bg-[#f0f2f5] flex items-center justify-center px-4">
      <div className="w-full max-w-sm">

        {/* Logo */}
        <div className="flex flex-col items-center mb-8">
          <div className="w-12 h-12 bg-primary rounded-xl flex items-center justify-center mb-3">
            <span className="text-white font-bold text-xl">گ</span>
          </div>
          <h1 className="text-xl font-bold text-dark">{t('auth.loginTitle')}</h1>
        </div>

        {/* Card */}
        <div className="bg-white border border-[#e8e8e8] rounded-xl p-6 shadow-sm">

          {/* API error */}
          {loginError && (
            <div className="flex items-center gap-2 bg-no-bg border border-no/30 text-no rounded-lg px-3 py-2.5 mb-4 text-sm">
              <AlertCircle size={16} className="shrink-0" />
              <span>{loginError}</span>
            </div>
          )}

          <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-4">
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
              autoComplete="current-password"
              error={errors.password ? t(errors.password.message ?? '') : undefined}
              {...register('password')}
            />

            <Button
              type="submit"
              fullWidth
              loading={loginPending}
              className="mt-2"
            >
              {t('auth.login')}
            </Button>
          </form>
        </div>

        {/* Switch to register */}
        <p className="text-center text-sm text-[#888888] mt-4">
          {t('auth.noAccount')}{' '}
          <Link to="/register" className="text-primary font-medium hover:underline">
            {t('auth.loginLink')}
          </Link>
        </p>
      </div>
    </div>
  )
}
