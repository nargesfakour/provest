import { createBrowserRouter, Navigate, Outlet } from 'react-router-dom'
import { useAuthStore } from '@/stores/authStore'
import { useAdminAuthStore } from '@/stores/adminAuthStore'
import UserLayout from '@/components/layout/UserLayout'
import AdminLayout from '@/components/layout/AdminLayout'

import LandingPage from '@/pages/public/LandingPage'
import LoginPage from '@/pages/public/LoginPage'
import RegisterPage from '@/pages/public/RegisterPage'

import EventsPage from '@/pages/user/EventsPage'
import PortfolioPage from '@/pages/user/PortfolioPage'
import OrdersPage from '@/pages/user/OrdersPage'
import WalletPage from '@/pages/user/WalletPage'
import DepositPage from '@/pages/user/DepositPage'
import WithdrawPage from '@/pages/user/WithdrawPage'
import PnlPage from '@/pages/user/PnlPage'
import TransactionsPage from '@/pages/user/TransactionsPage'

import AdminLoginPage from '@/pages/admin/AdminLoginPage'
import AdminDashboardPage from '@/pages/admin/AdminDashboardPage'
import AdminCategoriesPage from '@/pages/admin/AdminCategoriesPage'
import AdminEventsPage from '@/pages/admin/AdminEventsPage'
import AdminEventDetailPage from '@/pages/admin/AdminEventDetailPage'
import AdminUsersPage from '@/pages/admin/AdminUsersPage'
import AdminUserDetailPage from '@/pages/admin/AdminUserDetailPage'
import AdminDepositsPage from '@/pages/admin/AdminDepositsPage'
import AdminWithdrawalsPage from '@/pages/admin/AdminWithdrawalsPage'
import AdminReportsPage from '@/pages/admin/AdminReportsPage'
import AdminAdminsPage from '@/pages/admin/AdminAdminsPage'

// Redirect authenticated users away from login/register
function GuestGuard() {
  const token = useAuthStore((s) => s.token)
  if (token) return <Navigate to="/app/events" replace />
  return <Outlet />
}

// Require user authentication
function AuthGuard() {
  const token = useAuthStore((s) => s.token)
  if (!token) return <Navigate to="/login" replace />
  return <Outlet />
}

// Redirect authenticated admins away from admin login
function AdminGuestGuard() {
  const token = useAdminAuthStore((s) => s.token)
  if (token) return <Navigate to="/admin/dashboard" replace />
  return <Outlet />
}

// Require admin authentication
function AdminGuard() {
  const token = useAdminAuthStore((s) => s.token)
  if (!token) return <Navigate to="/admin/login" replace />
  return <Outlet />
}

export const router = createBrowserRouter([
  // Public
  { path: '/', element: <LandingPage /> },

  // Guest-only (user)
  {
    element: <GuestGuard />,
    children: [
      { path: '/login', element: <LoginPage /> },
      { path: '/register', element: <RegisterPage /> },
    ],
  },

  // Authenticated user panel
  {
    path: '/app',
    element: <AuthGuard />,
    children: [
      {
        element: <UserLayout />,
        children: [
          { index: true, element: <Navigate to="/app/events" replace /> },
          { path: 'events', element: <EventsPage /> },
          { path: 'portfolio', element: <PortfolioPage /> },
          { path: 'orders', element: <OrdersPage /> },
          { path: 'wallet', element: <WalletPage /> },
          { path: 'deposit', element: <DepositPage /> },
          { path: 'withdraw', element: <WithdrawPage /> },
          { path: 'pnl', element: <PnlPage /> },
          { path: 'transactions', element: <TransactionsPage /> },
        ],
      },
    ],
  },

  // Guest-only (admin)
  {
    element: <AdminGuestGuard />,
    children: [{ path: '/admin/login', element: <AdminLoginPage /> }],
  },

  // Authenticated admin panel
  {
    path: '/admin',
    element: <AdminGuard />,
    children: [
      {
        element: <AdminLayout />,
        children: [
          { index: true, element: <Navigate to="/admin/dashboard" replace /> },
          { path: 'dashboard',        element: <AdminDashboardPage />    },
          { path: 'categories',       element: <AdminCategoriesPage />   },
          { path: 'events',           element: <AdminEventsPage />       },
          { path: 'events/:ulid',     element: <AdminEventDetailPage />  },
          { path: 'users',            element: <AdminUsersPage />        },
          { path: 'users/:ulid',      element: <AdminUserDetailPage />   },
          { path: 'deposits',         element: <AdminDepositsPage />     },
          { path: 'withdrawals',      element: <AdminWithdrawalsPage />  },
          { path: 'reports',          element: <AdminReportsPage />      },
          { path: 'admins',           element: <AdminAdminsPage />       },
        ],
      },
    ],
  },

  // 404
  { path: '*', element: <Navigate to="/" replace /> },
])
