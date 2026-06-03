import { useNavigate } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useTranslation } from 'react-i18next'
import { AlertCircle, ShieldCheck } from 'lucide-react'

import { useAdminAuth } from '@/hooks/useAdminAuth'
import Button from '@/components/ui/Button'
import Input from '@/components/ui/Input'

const schema = z.object({
  email: z.string().min(1, 'auth.errors.emailRequired').email('auth.errors.emailInvalid'),
  password: z.string().min(1, 'auth.errors.passwordRequired').min(8, 'auth.errors.passwordMin'),
})

type FormValues = z.infer<typeof schema>

export default function AdminLoginPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { login, loginPending, loginError } = useAdminAuth()

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<FormValues>({ resolver: zodResolver(schema) })

  async function onSubmit(values: FormValues) {
    try {
      await login(values)
      navigate('/admin/dashboard', { replace: true })
    } catch {
      // error displayed via loginError
    }
  }

  return (
    <div className="min-h-screen bg-dark flex items-center justify-center px-4">
      <div className="w-full max-w-sm">

        {/* Logo */}
        <div className="flex flex-col items-center mb-8">
          <div className="w-14 h-14 bg-primary/20 border border-primary/30 rounded-xl flex items-center justify-center mb-3">
            <ShieldCheck size={28} className="text-primary" />
          </div>
          <h1 className="text-xl font-bold text-white">{t('auth.adminLoginTitle')}</h1>
          <p className="text-[#6b7a8d] text-sm mt-1">{t('app.name')}</p>
        </div>

        {/* Card */}
        <div className="bg-[#1e2c3d] border border-[#2a3a4e] rounded-xl p-6">

          {/* API error */}
          {loginError && (
            <div className="flex items-center gap-2 bg-no-bg border border-no/30 text-no rounded-lg px-3 py-2.5 mb-4 text-sm">
              <AlertCircle size={16} className="shrink-0" />
              <span>{loginError}</span>
            </div>
          )}

          <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-4">
            <div className="[&_label]:text-[#aabbcc] [&_input]:bg-[#253444] [&_input]:border-[#2a3a4e] [&_input]:text-white [&_input::placeholder]:text-[#4a5a6a] [&_input:focus]:border-primary">
              <Input
                label={t('auth.email')}
                type="email"
                placeholder={t('auth.emailPlaceholder')}
                autoComplete="email"
                error={errors.email ? t(errors.email.message ?? '') : undefined}
                {...register('email')}
              />
            </div>

            <div className="[&_label]:text-[#aabbcc] [&_input]:bg-[#253444] [&_input]:border-[#2a3a4e] [&_input]:text-white [&_input::placeholder]:text-[#4a5a6a] [&_input:focus]:border-primary">
              <Input
                label={t('auth.password')}
                type="password"
                placeholder={t('auth.passwordPlaceholder')}
                autoComplete="current-password"
                error={errors.password ? t(errors.password.message ?? '') : undefined}
                {...register('password')}
              />
            </div>

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

        {/* Back to site */}
        <p className="text-center text-sm text-[#4a5a6a] mt-4">
          <a href="/" className="hover:text-[#aabbcc] transition-colors">
            ← بازگشت به سایت
          </a>
        </p>
      </div>
    </div>
  )
}
