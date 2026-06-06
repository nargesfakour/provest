import { useState } from 'react'
import { NavLink, Outlet, useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import {
  LayoutDashboard,
  Layers,
  FolderTree,
  Users,
  UserCog,
  ArrowDownLeft,
  ArrowUpRight,
  FileBarChart,
  LogOut,
  ShieldCheck,
  Menu,
} from 'lucide-react'

import { useAdminAuthStore } from '@/stores/adminAuthStore'
import { adminLogout } from '@/api/adminAuth'

interface NavItem {
  path: string
  labelKey: string
  icon: React.ReactNode
}

const navItems: NavItem[] = [
  { path: '/admin/dashboard',   labelKey: 'admin.nav.dashboard',   icon: <LayoutDashboard size={18} /> },
  { path: '/admin/events',      labelKey: 'admin.nav.events',      icon: <Layers          size={18} /> },
  { path: '/admin/categories',  labelKey: 'admin.nav.categories',  icon: <FolderTree      size={18} /> },
  { path: '/admin/users',       labelKey: 'admin.nav.users',       icon: <Users           size={18} /> },
  { path: '/admin/admins',      labelKey: 'admin.nav.admins',      icon: <UserCog         size={18} /> },
  { path: '/admin/deposits',    labelKey: 'admin.nav.deposits',    icon: <ArrowDownLeft   size={18} /> },
  { path: '/admin/withdrawals', labelKey: 'admin.nav.withdrawals', icon: <ArrowUpRight    size={18} /> },
  { path: '/admin/reports',     labelKey: 'admin.nav.reports',     icon: <FileBarChart    size={18} /> },
]

export default function AdminLayout() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const admin = useAdminAuthStore((s) => s.admin)
  const clearAuth = useAdminAuthStore((s) => s.clearAuth)
  const [sidebarOpen, setSidebarOpen] = useState(false)

  async function handleLogout() {
    try { await adminLogout() } catch { /* ignore */ }
    clearAuth()
    navigate('/admin/login', { replace: true })
  }

  const initials = admin?.name
    ? admin.name.trim().split(' ').map((w) => w[0]).slice(0, 2).join('')
    : 'A'

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

        {/* Logo + panel label */}
        <div className="px-5 h-16 border-b border-white/10 flex items-center gap-3">
          <div className="w-8 h-8 bg-primary/20 border border-primary/30 rounded-lg flex items-center justify-center shrink-0">
            <ShieldCheck size={16} className="text-primary" />
          </div>
          <div className="min-w-0">
            <p className="text-white font-bold text-sm leading-tight">{t('app.name')}</p>
            <p className="text-[#6b7a8d] text-xs">پنل مدیریت</p>
          </div>
        </div>

        {/* Nav items */}
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

        {/* Admin info + logout */}
        <div className="border-t border-white/10 p-4 space-y-3">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 rounded-full bg-primary/20 flex items-center justify-center shrink-0">
              <span className="text-primary text-xs font-bold">{initials}</span>
            </div>
            <div className="min-w-0">
              <p className="text-white text-sm font-medium truncate">
                {admin?.name ?? '—'}
              </p>
              <p className="text-[#6b7a8d] text-xs truncate">
                {admin?.roles?.[0] ?? 'admin'}
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
            <ShieldCheck size={16} className="hidden lg:block text-primary" />
            <span className="text-dark font-medium text-sm">پنل مدیریت گومان</span>
          </div>
          <div className="flex items-center gap-2 text-sm">
            <span className="hidden sm:inline text-[#888888] truncate max-w-[200px]">{admin?.email}</span>
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
