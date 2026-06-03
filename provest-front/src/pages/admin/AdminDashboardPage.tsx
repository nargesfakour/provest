import { useQuery } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import {
  AreaChart,
  Area,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from 'recharts'
import { Users, Layers, TrendingUp, Coins } from 'lucide-react'

import { getDashboardStats, getVolumeChart } from '@/api/adminReports'
import { getAdminDeposits } from '@/api/adminDeposits'
import { getAdminEvents } from '@/api/adminEvents'
import Card, { CardHeader } from '@/components/ui/Card'
import Badge from '@/components/ui/Badge'
import Skeleton from '@/components/ui/Skeleton'
import { formatDate } from '@/utils/date'
import type { DepositStatus, EventStatus } from '@/types/domain'

// ─── Stat Card ───────────────────────────────────────────────────────────────

interface StatCardProps {
  label: string
  value: string | number
  icon: React.ReactNode
  color: string
  loading: boolean
}

function StatCard({ label, value, icon, color, loading }: StatCardProps) {
  return (
    <Card>
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm text-[#888888] mb-1">{label}</p>
          {loading ? (
            <Skeleton className="h-7 w-28 mt-1" />
          ) : (
            <p className="text-2xl font-bold text-dark">{value}</p>
          )}
        </div>
        <div className={['w-12 h-12 rounded-xl flex items-center justify-center', color].join(' ')}>
          {icon}
        </div>
      </div>
    </Card>
  )
}

// ─── Badge maps ──────────────────────────────────────────────────────────────

const depositBadgeVariant: Record<DepositStatus, 'success' | 'pending' | 'danger'> = {
  confirmed: 'success',
  pending: 'pending',
  rejected: 'danger',
}

const depositStatusLabel: Record<DepositStatus, string> = {
  confirmed: 'تأیید شده',
  pending: 'در انتظار',
  rejected: 'رد شده',
}

const eventBadgeVariant: Record<EventStatus, 'open' | 'closed' | 'settled' | 'pending'> = {
  Open: 'open',
  Closed: 'closed',
  Settled: 'settled',
  Pending: 'pending',
}

const eventStatusLabel: Record<EventStatus, string> = {
  Open: 'باز',
  Closed: 'بسته',
  Settled: 'تسویه',
  Pending: 'در انتظار',
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function AdminDashboardPage() {
  const navigate = useNavigate()

  const { data: stats, isLoading: statsLoading } = useQuery({
    queryKey: ['admin-stats'],
    queryFn: getDashboardStats,
    staleTime: 60_000,
  })

  const { data: chartData = [], isLoading: chartLoading } = useQuery({
    queryKey: ['admin-volume-chart'],
    queryFn: () => getVolumeChart(7),
    staleTime: 60_000,
  })

  const { data: depositsData, isLoading: depositsLoading } = useQuery({
    queryKey: ['admin-recent-deposits'],
    queryFn: () => getAdminDeposits({ per_page: 6 }),
    staleTime: 30_000,
  })

  const { data: eventsData, isLoading: eventsLoading } = useQuery({
    queryKey: ['admin-recent-events'],
    queryFn: () => getAdminEvents({ per_page: 6 }),
    staleTime: 30_000,
  })

  const deposits = depositsData?.items ?? []
  const events = eventsData?.items ?? []

  return (
    <div className="p-4 md:p-6 space-y-4 md:space-y-6">

      {/* ── Stats row ─────────────────────────────────────────────────── */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          label="کل کاربران"
          value={stats?.total_users.toLocaleString('fa-IR') ?? '—'}
          icon={<Users size={22} className="text-primary" />}
          color="bg-primary/10"
          loading={statsLoading}
        />
        <StatCard
          label="ایونت‌های فعال"
          value={stats?.active_events.toLocaleString('fa-IR') ?? '—'}
          icon={<Layers size={22} className="text-[#3366cc]" />}
          color="bg-[#e8f0ff]"
          loading={statsLoading}
        />
        <StatCard
          label="حجم معاملات (USDT)"
          value={stats ? `${stats.total_volume} USDT` : '—'}
          icon={<TrendingUp size={22} className="text-yes" />}
          color="bg-yes-bg"
          loading={statsLoading}
        />
        <StatCard
          label="کارمزد امروز (USDT)"
          value={stats ? `${stats.today_fees} USDT` : '—'}
          icon={<Coins size={22} className="text-[#f07c30]" />}
          color="bg-[#fff3e8]"
          loading={statsLoading}
        />
      </div>

      {/* ── Middle: Recent deposits + Recent events ───────────────────── */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">

        {/* Recent deposits */}
        <Card>
          <CardHeader
            title="آخرین واریزها"
            action={
              <button
                onClick={() => navigate('/admin/deposits')}
                className="text-xs text-primary hover:underline"
              >
                مشاهده همه
              </button>
            }
          />
          {depositsLoading ? (
            <div className="space-y-3">
              {Array.from({ length: 4 }).map((_, i) => (
                <div key={i} className="flex items-center justify-between">
                  <Skeleton className="h-4 w-32" />
                  <Skeleton className="h-5 w-20" />
                </div>
              ))}
            </div>
          ) : deposits.length === 0 ? (
            <p className="text-sm text-[#888888] text-center py-4">واریزی وجود ندارد</p>
          ) : (
            <div className="space-y-0.5">
              {deposits.map((deposit) => (
                <div
                  key={deposit.ulid}
                  className="flex items-center justify-between py-2.5 border-b border-[#f0f2f5] last:border-0"
                >
                  <div>
                    <p className="text-sm font-medium text-dark">
                      {deposit.amount}{' '}
                      <span className="text-[#888888] font-normal">USDT</span>
                    </p>
                    <p className="text-xs text-[#888888]">
                      {deposit.network} · {formatDate(deposit.created_at)}
                    </p>
                  </div>
                  <Badge variant={depositBadgeVariant[deposit.status]}>
                    {depositStatusLabel[deposit.status]}
                  </Badge>
                </div>
              ))}
            </div>
          )}
        </Card>

        {/* Recent events */}
        <Card>
          <CardHeader
            title="آخرین ایونت‌ها"
            action={
              <button
                onClick={() => navigate('/admin/events')}
                className="text-xs text-primary hover:underline"
              >
                مشاهده همه
              </button>
            }
          />
          {eventsLoading ? (
            <div className="space-y-3">
              {Array.from({ length: 4 }).map((_, i) => (
                <div key={i} className="flex items-center justify-between">
                  <Skeleton className="h-4 w-40" />
                  <Skeleton className="h-5 w-16" />
                </div>
              ))}
            </div>
          ) : events.length === 0 ? (
            <p className="text-sm text-[#888888] text-center py-4">ایونتی وجود ندارد</p>
          ) : (
            <div className="space-y-0.5">
              {events.map((event) => (
                <div
                  key={event.ulid}
                  onClick={() => navigate(`/admin/events/${event.ulid}`)}
                  className="flex items-center justify-between py-2.5 border-b border-[#f0f2f5] last:border-0 cursor-pointer hover:bg-[#f8f9fa] -mx-1 px-1 rounded-lg transition-colors"
                >
                  <div className="min-w-0 flex-1 ml-3">
                    <p className="text-sm font-medium text-dark truncate">{event.title}</p>
                    <p className="text-xs text-[#888888]">{event.sub_category.name}</p>
                  </div>
                  <Badge variant={eventBadgeVariant[event.status]}>
                    {eventStatusLabel[event.status]}
                  </Badge>
                </div>
              ))}
            </div>
          )}
        </Card>
      </div>

      {/* ── Volume chart ──────────────────────────────────────────────── */}
      <Card>
        <CardHeader title="حجم معاملات ۷ روز گذشته" />
        {chartLoading ? (
          <Skeleton className="h-56 w-full" />
        ) : chartData.length === 0 ? (
          <p className="text-sm text-[#888888] text-center py-10">داده‌ای موجود نیست</p>
        ) : (
          <div className="h-56">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={chartData} margin={{ top: 4, right: 4, left: 4, bottom: 0 }}>
                <defs>
                  <linearGradient id="volumeGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#00c896" stopOpacity={0.2} />
                    <stop offset="95%" stopColor="#00c896" stopOpacity={0} />
                  </linearGradient>
                  <linearGradient id="feeGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#f07c30" stopOpacity={0.15} />
                    <stop offset="95%" stopColor="#f07c30" stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke="#f0f2f5" />
                <XAxis
                  dataKey="date"
                  tick={{ fontSize: 11, fill: '#888888' }}
                  axisLine={false}
                  tickLine={false}
                />
                <YAxis
                  tick={{ fontSize: 11, fill: '#888888' }}
                  axisLine={false}
                  tickLine={false}
                  width={60}
                />
                <Tooltip
                  contentStyle={{
                    background: '#1a2332',
                    border: 'none',
                    borderRadius: 8,
                    color: '#fff',
                    fontSize: 12,
                  }}
                  labelStyle={{ color: '#aabbcc' }}
                />
                <Area
                  type="monotone"
                  dataKey="volume"
                  name="حجم معاملات"
                  stroke="#00c896"
                  strokeWidth={2}
                  fill="url(#volumeGrad)"
                />
                <Area
                  type="monotone"
                  dataKey="fees"
                  name="کارمزد"
                  stroke="#f07c30"
                  strokeWidth={2}
                  fill="url(#feeGrad)"
                />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        )}
      </Card>
    </div>
  )
}
