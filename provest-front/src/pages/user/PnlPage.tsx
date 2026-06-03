import { useQuery } from '@tanstack/react-query'
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Cell,
} from 'recharts'
import { TrendingUp, TrendingDown, Layers, Target } from 'lucide-react'

import { getPositions } from '@/api/positions'
import Card, { CardHeader } from '@/components/ui/Card'
import Badge from '@/components/ui/Badge'
import Skeleton, { SkeletonCard } from '@/components/ui/Skeleton'
import type { Side } from '@/types/domain'

// ─── Stat card ────────────────────────────────────────────────────────────────

interface StatCardProps {
  label: string
  value: string
  icon: React.ReactNode
  color: string
  loading: boolean
}

function StatCard({ label, value, icon, color, loading }: StatCardProps) {
  return (
    <Card>
      <div className="flex items-center justify-between">
        <div>
          <p className="text-xs text-[#888888] mb-1">{label}</p>
          {loading ? (
            <Skeleton className="h-7 w-28 mt-1" />
          ) : (
            <p className="text-xl font-bold text-dark">{value}</p>
          )}
        </div>
        <div className={['w-11 h-11 rounded-xl flex items-center justify-center', color].join(' ')}>
          {icon}
        </div>
      </div>
    </Card>
  )
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function PnlPage() {
  const { data: positions = [], isLoading, isError, refetch } = useQuery({
    queryKey: ['positions'],
    queryFn: getPositions,
    staleTime: 30_000,
  })

  // Computed stats
  const totalPnl = positions.reduce((s, p) => s + parseFloat(p.pnl), 0)
  const invested = positions.reduce((s, p) => s + parseFloat(p.avg_price) * parseFloat(p.quantity), 0)
  const currentVal = positions.reduce((s, p) => s + parseFloat(p.current_price) * parseFloat(p.quantity), 0)
  const winners = positions.filter((p) => parseFloat(p.pnl) > 0).length
  const winRate = positions.length > 0 ? ((winners / positions.length) * 100).toFixed(0) : '0'

  const pnlPositive = totalPnl >= 0

  // Chart data: one bar per position (sorted by P&L)
  const chartData = [...positions]
    .sort((a, b) => parseFloat(b.pnl) - parseFloat(a.pnl))
    .map((p) => ({
      name: p.event_title.length > 20 ? p.event_title.slice(0, 20) + '…' : p.event_title,
      pnl: parseFloat(p.pnl),
      side: p.side,
    }))

  if (isError) {
    return (
      <div className="p-8 flex flex-col items-center gap-4">
        <p className="text-[#888888]">خطا در دریافت داده</p>
        <button
          onClick={() => refetch()}
          className="text-sm text-primary hover:underline"
        >
          تلاش مجدد
        </button>
      </div>
    )
  }

  return (
    <div className="p-4 md:p-6 space-y-4 md:space-y-6">
      <h2 className="text-base md:text-lg font-bold text-dark">سود و زیان</h2>

      {/* ── Stats row ─────────────────────────────────────────────── */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          label="سود/زیان کل"
          value={`${pnlPositive ? '+' : ''}${totalPnl.toFixed(4)} USDT`}
          icon={pnlPositive ? <TrendingUp size={20} className="text-yes" /> : <TrendingDown size={20} className="text-no" />}
          color={pnlPositive ? 'bg-yes-bg' : 'bg-no-bg'}
          loading={isLoading}
        />
        <StatCard
          label="ارزش سرمایه‌گذاری"
          value={`${invested.toFixed(4)} USDT`}
          icon={<Layers size={20} className="text-[#3366cc]" />}
          color="bg-[#e8f0ff]"
          loading={isLoading}
        />
        <StatCard
          label="ارزش فعلی"
          value={`${currentVal.toFixed(4)} USDT`}
          icon={<Target size={20} className="text-primary" />}
          color="bg-primary/10"
          loading={isLoading}
        />
        <StatCard
          label="نرخ موفقیت"
          value={`${winRate}%`}
          icon={<TrendingUp size={20} className="text-[#f07c30]" />}
          color="bg-[#fff3e8]"
          loading={isLoading}
        />
      </div>

      {/* ── P&L chart ─────────────────────────────────────────────── */}
      {isLoading ? (
        <Skeleton className="h-52 w-full" />
      ) : chartData.length > 0 && (
        <Card>
          <CardHeader title="سود/زیان به تفکیک ایونت" />
          <div className="h-52">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={chartData} margin={{ top: 4, right: 4, left: 0, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="#f0f2f5" />
                <XAxis
                  dataKey="name"
                  tick={{ fontSize: 10, fill: '#888888' }}
                  axisLine={false}
                  tickLine={false}
                />
                <YAxis
                  tick={{ fontSize: 10, fill: '#888888' }}
                  axisLine={false}
                  tickLine={false}
                  width={55}
                />
                <Tooltip
                  contentStyle={{
                    background: '#1a2332',
                    border: 'none',
                    borderRadius: 8,
                    color: '#fff',
                    fontSize: 12,
                  }}
                  formatter={(val: number) => [`${val.toFixed(4)} USDT`, 'P&L']}
                />
                <Bar dataKey="pnl" radius={[4, 4, 0, 0]}>
                  {chartData.map((entry, i) => (
                    <Cell
                      key={i}
                      fill={entry.pnl >= 0 ? '#00a87a' : '#e05050'}
                      fillOpacity={0.85}
                    />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </Card>
      )}

      {/* ── Positions table ────────────────────────────────────────── */}
      <Card padding={false}>
        <div className="p-4">
          <CardHeader title="پوزیشن‌های فعال" />
        </div>
        {isLoading ? (
          <div className="px-4 pb-4 space-y-3">
            {Array.from({ length: 4 }).map((_, i) => <SkeletonCard key={i} />)}
          </div>
        ) : positions.length === 0 ? (
          <p className="px-4 pb-8 text-center text-sm text-[#888888]">پوزیشن فعالی ندارید</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-[#e8e8e8]">
                  <th className="px-4 py-3 text-right font-medium text-[#888888]">ایونت</th>
                  <th className="px-4 py-3 text-right font-medium text-[#888888]">جهت</th>
                  <th className="px-4 py-3 text-right font-medium text-[#888888] hidden sm:table-cell">تعداد</th>
                  <th className="px-4 py-3 text-right font-medium text-[#888888] hidden md:table-cell">قیمت میانگین</th>
                  <th className="px-4 py-3 text-right font-medium text-[#888888] hidden md:table-cell">قیمت فعلی</th>
                  <th className="px-4 py-3 text-right font-medium text-[#888888]">سود/زیان</th>
                </tr>
              </thead>
              <tbody>
                {positions.map((p, i) => {
                  const pos = parseFloat(p.pnl) >= 0
                  return (
                    <tr key={i} className="border-b border-[#f0f2f5] hover:bg-[#f8f9fa]">
                      <td className="px-4 py-3 font-medium text-dark max-w-[160px] md:max-w-[220px]">
                        <span className="truncate block">{p.event_title}</span>
                      </td>
                      <td className="px-4 py-3">
                        <Badge variant={p.side as Side}>{p.side.toUpperCase()}</Badge>
                      </td>
                      <td className="px-4 py-3 hidden sm:table-cell">{p.quantity}</td>
                      <td className="px-4 py-3 hidden md:table-cell">{p.avg_price}</td>
                      <td className="px-4 py-3 hidden md:table-cell">{p.current_price}</td>
                      <td className={['px-4 py-3 font-bold', pos ? 'text-yes' : 'text-no'].join(' ')}>
                        {pos ? '+' : ''}{p.pnl}
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
        )}
      </Card>
    </div>
  )
}
