import { useState } from 'react'
import { NavLink, Outlet, useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import {
  LayoutGrid,
  ClipboardList,
  Briefcase,
  Wallet,
  ArrowDownLeft,
  ArrowUpRight,
  BarChart2,
  Receipt,
  LogOut,
  ChevronLeft,
  Menu,
} from 'lucide-react'

import { useAuthStore } from '@/stores/authStore'
import { logout } from '@/api/auth'
import { formatUsdt } from '@/utils/currency'
import { useUserRealtime } from '@/hooks/useRealtime'

interface NavItem {
  path: string
  labelKey: string
  icon: React.ReactNode
}

const navItems: NavItem[] = [
  { path: '/app/events',       labelKey: 'nav.events',       icon: <LayoutGrid   size={18} /> },
  { path: '/app/orders',       labelKey: 'nav.orders',       icon: <ClipboardList size={18} /> },
  { path: '/app/portfolio',    labelKey: 'nav.portfolio',    icon: <Briefcase    size={18} /> },
  { path: '/app/wallet',       labelKey: 'nav.wallet',       icon: <Wallet       size={18} /> },
  { path: '/app/deposit',      labelKey: 'nav.deposit',      icon: <ArrowDownLeft size={18} /> },
  { path: '/app/withdraw',     labelKey: 'nav.withdraw',     icon: <ArrowUpRight size={18} /> },
  { path: '/app/pnl',          labelKey: 'nav.pnl',          icon: <BarChart2    size={18} /> },
  { path: '/app/transactions', labelKey: 'nav.transactions', icon: <Receipt      size={18} /> },
]

export default function UserLayout() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const user = useAuthStore((s) => s.user)
  const clearAuth = useAuthStore((s) => s.clearAuth)
  const [sidebarOpen, setSidebarOpen] = useState(false)

  useUserRealtime()

  async function handleLogout() {
    try { await logout() } catch { /* ignore */ }
    clearAuth()
    navigate('/login', { replace: true })
  }

  const initials = user?.name
    ? user.name.trim().split(' ').map((w) => w[0]).slice(0, 2).join('')
    : '؟'

  return (
    <div className="flex min-h-screen bg-[#f0f2f5]">

      {/* ── Backdrop (mobile/tablet) ──────────────────────────────────── */}
      {sidebarOpen && (
        <div
          className="fixed inset-0 z-20 bg-black/40 lg:hidden"
          onClick={() => setSidebarOpen(false)}
        />
      )}

      {/* ── Sidebar ─────────────────────────────────────────────────────── */}
      <aside
        className={[
          'w-[220px] fixed top-0 right-0 h-screen bg-dark flex flex-col z-30 shrink-0',
          'transition-transform duration-200 ease-in-out',
          sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0',
        ].join(' ')}
      >

        {/* Logo */}
        <div className="flex items-center gap-2.5 px-5 h-16 border-b border-white/10">
          <div className="w-8 h-8 bg-primary rounded-lg flex items-center justify-center shrink-0">
            <span className="text-white font-bold text-sm">گ</span>
          </div>
          <span className="text-white font-bold text-base">{t('app.name')}</span>
        </div>

        {/* Nav */}
        <nav className="flex-1 overflow-y-auto py-4 px-3 space-y-0.5">
          {navItems.map((item) => (
            <NavLink
              key={item.path}
              to={item.path}
              onClick={() => setSidebarOpen(false)}
              className={({ isActive }) =>
                [
                  'relative flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                  isActive
                    ? 'bg-primary/10 text-primary'
                    : 'text-[#8899aa] hover:bg-white/5 hover:text-white',
                ].join(' ')
              }
            >
              {({ isActive }) => (
                <>
                  <span
                    className={[
                      'absolute right-0 w-0.5 h-6 rounded-l bg-primary transition-opacity',
                      isActive ? 'opacity-100' : 'opacity-0',
                    ].join(' ')}
                  />
                  <span className="shrink-0">{item.icon}</span>
                  <span>{t(item.labelKey)}</span>
                </>
              )}
            </NavLink>
          ))}
        </nav>

        {/* User info + logout */}
        <div className="border-t border-white/10 p-4 space-y-3">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-full bg-primary/20 flex items-center justify-center shrink-0">
              <span className="text-primary text-xs font-bold">{initials}</span>
            </div>
            <div className="min-w-0">
              <p className="text-white text-sm font-medium truncate">
                {user?.name ?? '—'}
              </p>
              <p className="text-[#8899aa] text-xs font-mono">
                {user?.balance ? formatUsdt(user.balance) : '—'}
              </p>
            </div>
          </div>

          <button
            onClick={handleLogout}
            className="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-[#8899aa] hover:bg-white/5 hover:text-no transition-colors"
          >
            <LogOut size={16} />
            <span>{t('nav.logout')}</span>
          </button>
        </div>
      </aside>

      {/* ── Main area ───────────────────────────────────────────────────── */}
      <div className="flex-1 lg:mr-[220px] flex flex-col min-h-screen">

        {/* Topbar */}
        <header className="bg-white border-b border-[#e8e8e8] h-14 sticky top-0 z-20 flex items-center justify-between px-4 md:px-6">
          <div className="flex items-center gap-2">
            <button
              className="lg:hidden p-2 -mr-1 rounded-lg text-[#888888] hover:bg-[#f0f2f5] transition-colors"
              onClick={() => setSidebarOpen(true)}
              aria-label="منو"
            >
              <Menu size={20} />
            </button>
            <ChevronLeft size={16} className="hidden lg:block text-[#888888]" />
            <span className="text-dark font-medium text-sm">{t('app.name')}</span>
          </div>
          <div className="flex items-center gap-2">
            <span className="hidden sm:inline text-xs text-[#888888]">{t('wallet.balance')}:</span>
            <span className="text-sm font-semibold text-dark font-mono">
              {user?.balance ? formatUsdt(user.balance) : '—'}
            </span>
          </div>
        </header>

        {/* Page content */}
        <main className="flex-1">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
