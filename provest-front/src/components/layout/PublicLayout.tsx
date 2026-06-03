import { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import Button from '@/components/ui/Button'

interface PublicLayoutProps {
  children: ReactNode
}

export default function PublicLayout({ children }: PublicLayoutProps) {
  const { t } = useTranslation()

  return (
    <div className="min-h-screen bg-[#f0f2f5]">
      <header className="bg-white border-b border-[#e8e8e8] sticky top-0 z-40">
        <div className="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
          {/* Logo — right side (RTL) */}
          <Link to="/" className="flex items-center gap-2">
            <div className="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
              <span className="text-white font-bold text-sm">گ</span>
            </div>
            <span className="font-bold text-dark text-lg">{t('app.name')}</span>
          </Link>

          {/* Auth buttons — left side (RTL) */}
          <div className="flex items-center gap-3">
            <Link to="/login">
              <Button variant="ghost" size="sm">{t('auth.login')}</Button>
            </Link>
            <Link to="/register">
              <Button size="sm">{t('auth.register')}</Button>
            </Link>
          </div>
        </div>
      </header>

      <main>{children}</main>
    </div>
  )
}
