import { useState, useMemo } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { Search, Minus, Plus, AlertCircle, CheckCircle, LayoutGrid } from 'lucide-react'
import { useEventRealtime } from '@/hooks/useRealtime'

import { getEvents } from '@/api/events'
import { getOrderBook, getTrades } from '@/api/events'
import { createOrder } from '@/api/orders'
import { formatDate, timeAgo } from '@/utils/date'
import type { Event, PriceLevel, Trade, Side } from '@/types/domain'
import type { CreateOrderPayload } from '@/types/api'

import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import Skeleton from '@/components/ui/Skeleton'
import Input from '@/components/ui/Input'
import Modal from '@/components/ui/Modal'

// ─── Types ───────────────────────────────────────────────────────────────────

type MobileTab = 'events' | 'book' | 'form' | 'trades'

const MOBILE_TABS: { key: MobileTab; label: string }[] = [
  { key: 'events', label: 'ایونت‌ها' },
  { key: 'book',   label: 'دفتر سفارشات' },
  { key: 'form',   label: 'ثبت سفارش' },
  { key: 'trades', label: 'معاملات' },
]

// ─── Page ────────────────────────────────────────────────────────────────────

export default function EventsPage() {
  const [selectedUlid, setSelectedUlid] = useState<string | null>(null)
  const [search, setSearch] = useState('')
  const [mobileTab, setMobileTab] = useState<MobileTab>('events')
  const [tabletEventsOpen, setTabletEventsOpen] = useState(false)
  const { t } = useTranslation()

  const { data: eventsData, isLoading } = useQuery({
    queryKey: ['events', 'open'],
    queryFn: () => getEvents({ status: 'open', per_page: 100 }),
    staleTime: 30_000,
  })

  const events = eventsData?.items ?? []

  const filtered = useMemo(() => {
    if (!search.trim()) return events
    const q = search.toLowerCase()
    return events.filter(
      (e) =>
        e.title.toLowerCase().includes(q) ||
        e.sub_category.toLowerCase().includes(q),
    )
  }, [events, search])

  const selectedEvent = events.find((e) => e.ulid === selectedUlid) ?? null

  useEventRealtime(selectedUlid)

  function handleSelectEvent(ulid: string) {
    setSelectedUlid(ulid)
    setMobileTab('book')
    setTabletEventsOpen(false)
  }

  // ── Events list panel content (shared between sidebar & tablet modal) ──────
  const eventsListContent = (
    <>
      <div className="p-3 border-b border-[#e8e8e8] shrink-0">
        <div className="relative">
          <Search size={14} className="absolute right-3 top-1/2 -translate-y-1/2 text-[#bbbbbb]" />
          <input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder={t('events.search')}
            className="w-full pr-9 pl-3 py-2 text-sm border border-[#e8e8e8] rounded-lg focus:outline-none focus:border-primary"
          />
        </div>
      </div>

      <div className="flex-1 overflow-y-auto">
        {isLoading
          ? Array.from({ length: 6 }).map((_, i) => (
              <div key={i} className="p-3 border-b border-[#e8e8e8] space-y-2">
                <Skeleton className="h-3 w-1/2" />
                <Skeleton className="h-4 w-full" />
                <Skeleton className="h-3 w-3/4" />
              </div>
            ))
          : filtered.length === 0
            ? <p className="p-4 text-center text-sm text-[#888888]">{t('events.noEvents')}</p>
            : filtered.map((event) => (
                <EventListItem
                  key={event.ulid}
                  event={event}
                  active={event.ulid === selectedUlid}
                  onSelect={() => handleSelectEvent(event.ulid)}
                />
              ))}
      </div>
    </>
  )

  return (
    <>
      <div className="flex flex-col h-[calc(100vh-56px)] overflow-hidden">

        {/* ── Mobile tab bar (hidden on md+) ─────────────────────────── */}
        <div className="md:hidden flex border-b border-[#e8e8e8] bg-white shrink-0">
          {MOBILE_TABS.map((tab) => (
            <button
              key={tab.key}
              onClick={() => setMobileTab(tab.key)}
              className={[
                'flex-1 py-2.5 text-xs font-medium border-b-2 transition-colors',
                mobileTab === tab.key
                  ? 'border-primary text-primary'
                  : 'border-transparent text-[#888888]',
              ].join(' ')}
            >
              {tab.label}
            </button>
          ))}
        </div>

        {/* ── Content ────────────────────────────────────────────────── */}
        <div className="flex-1 flex overflow-hidden">

          {/* Events list sidebar:
              - Mobile: full-width when events tab, otherwise hidden
              - Tablet (md–lg): hidden (use modal instead)
              - Desktop (lg+): always visible 240px sidebar */}
          <div
            className={[
              'border-l border-[#e8e8e8] bg-white flex-col shrink-0',
              mobileTab === 'events' ? 'flex w-full' : 'hidden',
              'lg:flex lg:w-[240px]',
            ].join(' ')}
          >
            {eventsListContent}
          </div>

          {/* Trading area:
              - Mobile: hidden on events tab, shown on other tabs
              - Tablet+: always shown */}
          <div
            className={[
              'flex-1 flex-col overflow-hidden',
              mobileTab !== 'events' ? 'flex' : 'hidden',
              'md:flex',
            ].join(' ')}
          >
            {!selectedEvent ? (
              <div className="flex-1 flex flex-col items-center justify-center gap-4 text-[#888888] text-sm">
                <p>{t('events.selectEvent')}</p>
                <button
                  className="hidden md:flex lg:hidden items-center gap-2 px-4 py-2.5 bg-primary text-white rounded-lg text-sm font-medium min-h-[44px]"
                  onClick={() => setTabletEventsOpen(true)}
                >
                  <LayoutGrid size={16} />
                  انتخاب ایونت
                </button>
              </div>
            ) : (
              <TradingArea
                event={selectedEvent}
                mobileTab={mobileTab}
                onOpenEvents={() => setTabletEventsOpen(true)}
              />
            )}
          </div>
        </div>
      </div>

      {/* ── Tablet events modal (hidden on mobile & desktop) ──────────── */}
      <Modal
        isOpen={tabletEventsOpen}
        onClose={() => setTabletEventsOpen(false)}
        title="انتخاب ایونت"
        size="md"
      >
        <div className="flex flex-col gap-3" style={{ height: '55vh' }}>
          <div className="relative shrink-0">
            <Search size={14} className="absolute right-3 top-1/2 -translate-y-1/2 text-[#bbbbbb]" />
            <input
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder={t('events.search')}
              className="w-full pr-9 pl-3 py-2 text-sm border border-[#e8e8e8] rounded-lg focus:outline-none focus:border-primary"
            />
          </div>
          <div className="flex-1 overflow-y-auto border border-[#e8e8e8] rounded-lg">
            {filtered.length === 0
              ? <p className="p-4 text-center text-sm text-[#888888]">{t('events.noEvents')}</p>
              : filtered.map((event) => (
                  <EventListItem
                    key={event.ulid}
                    event={event}
                    active={event.ulid === selectedUlid}
                    onSelect={() => handleSelectEvent(event.ulid)}
                  />
                ))}
          </div>
        </div>
      </Modal>
    </>
  )
}

// ─── Event list item ─────────────────────────────────────────────────────────

function EventListItem({
  event,
  active,
  onSelect,
}: {
  event: Event
  active: boolean
  onSelect: () => void
}) {
  return (
    <button
      onClick={onSelect}
      className={[
        'w-full text-right p-3 border-b border-[#e8e8e8] transition-colors',
        active ? 'bg-primary/5 border-r-2 border-r-primary' : 'hover:bg-[#f0f2f5]',
      ].join(' ')}
    >
      <p className="text-xs text-[#888888] mb-0.5">{event.sub_category.name}</p>
      <p className={['text-sm font-medium leading-snug mb-2 line-clamp-2', active ? 'text-primary' : 'text-dark'].join(' ')}>
        {event.title}
      </p>
      <div className="flex gap-1.5">
        <span className="flex-1 text-center text-xs font-semibold text-yes bg-yes-bg rounded py-0.5">
          {event.yes_initial_price}
        </span>
        <span className="flex-1 text-center text-xs font-semibold text-no bg-no-bg rounded py-0.5">
          {event.no_initial_price}
        </span>
      </div>
    </button>
  )
}

// ─── Trading area ────────────────────────────────────────────────────────────

function TradingArea({
  event,
  mobileTab,
  onOpenEvents,
}: {
  event: Event
  mobileTab: MobileTab
  onOpenEvents: () => void
}) {
  const { t } = useTranslation()

  const { data: orderBook, isLoading: obLoading } = useQuery({
    queryKey: ['orderbook', event.ulid],
    queryFn: () => getOrderBook(event.ulid),
    refetchInterval: 5_000,
    staleTime: 3_000,
  })

  const { data: trades, isLoading: tradesLoading } = useQuery({
    queryKey: ['trades', event.ulid],
    queryFn: () => getTrades(event.ulid),
    refetchInterval: 10_000,
    staleTime: 5_000,
  })

  return (
    <div className="flex-1 flex flex-col overflow-hidden">
      {/* Event top bar */}
      <div className="bg-white border-b border-[#e8e8e8] px-4 py-2.5 md:py-3 flex items-center gap-3 flex-wrap shrink-0">
        <h2 className="font-semibold text-dark flex-1 min-w-0 truncate text-sm md:text-base">{event.title}</h2>
        <div className="flex items-center gap-2 md:gap-3 flex-wrap shrink-0">
          <Badge variant={event.status.toLowerCase() as 'open' | 'closed' | 'settled' | 'pending'}>
            {t(`status.${event.status}`)}
          </Badge>
          <span className="hidden md:inline text-sm text-[#888888]">{event.sub_category.name}</span>
          {event.ends_at && (
            <span className="hidden md:inline text-sm text-[#888888]">
              {t('events.endDate')}: {formatDate(event.ends_at)}
            </span>
          )}
          <span className="hidden lg:inline text-sm text-[#888888]">
            {t('events.fee')}: {(parseFloat(event.fee_rate) * 100).toFixed(1)}%
          </span>
          {/* Tablet: button to open events selector */}
          <button
            className="hidden md:flex lg:hidden items-center gap-1.5 px-3 py-1.5 text-xs bg-primary/10 text-primary rounded-lg font-medium min-h-[36px]"
            onClick={onOpenEvents}
          >
            <LayoutGrid size={13} />
            تغییر ایونت
          </button>
        </div>
      </div>

      {/* Trading columns:
          Mobile: single panel per tab (flex)
          Tablet (md): 2-col grid
          Desktop (lg): 3-col grid */}
      <div className="flex-1 overflow-hidden md:grid md:grid-cols-2 lg:grid-cols-3 md:divide-x md:divide-[#e8e8e8]">

        {/* Order Book */}
        <div
          className={[
            'overflow-hidden',
            mobileTab === 'book' ? 'flex flex-col h-full' : 'hidden',
            'md:flex md:flex-col',
          ].join(' ')}
        >
          <OrderBook
            yesLevels={orderBook?.yes ?? []}
            noLevels={orderBook?.no ?? []}
            loading={obLoading}
            yesInitial={event.yes_initial_price}
            noInitial={event.no_initial_price}
          />
        </div>

        {/* Order Form */}
        <div
          className={[
            'overflow-hidden',
            mobileTab === 'form' ? 'flex flex-col h-full' : 'hidden',
            'md:flex md:flex-col',
          ].join(' ')}
        >
          <OrderForm event={event} />
        </div>

        {/* Recent Trades:
            Mobile: shown on trades tab
            Tablet (md): hidden (2-col layout)
            Desktop (lg): always shown */}
        <div
          className={[
            'overflow-hidden',
            mobileTab === 'trades' ? 'flex flex-col h-full md:hidden lg:flex lg:flex-col' : 'hidden lg:flex lg:flex-col',
          ].join(' ')}
        >
          <RecentTrades trades={trades ?? []} loading={tradesLoading} />
        </div>
      </div>
    </div>
  )
}

// ─── Order Book ──────────────────────────────────────────────────────────────

function OrderBook({
  yesLevels,
  noLevels,
  loading,
  yesInitial,
  noInitial,
}: {
  yesLevels: PriceLevel[]
  noLevels: PriceLevel[]
  loading: boolean
  yesInitial: string
  noInitial: string
}) {
  const { t } = useTranslation()

  const maxYesDepth = Math.max(...yesLevels.map((l) => parseFloat(l.depth)), 1)
  const maxNoDepth = Math.max(...noLevels.map((l) => parseFloat(l.depth)), 1)

  const midPrice = yesLevels[0]?.price ?? yesInitial
  const midNo = noLevels[0]?.price ?? noInitial

  return (
    <div className="flex flex-col overflow-hidden bg-white w-full h-full">
      {/* Header */}
      <div className="grid grid-cols-2 px-3 py-2 text-xs text-[#888888] font-medium border-b border-[#e8e8e8] shrink-0">
        <span>{t('orderBook.price')}</span>
        <span className="text-left">{t('orderBook.quantity')}</span>
      </div>

      <div className="flex-1 overflow-y-auto">
        {loading ? (
          <div className="p-3 space-y-1.5">
            {Array.from({ length: 8 }).map((_, i) => <Skeleton key={i} className="h-5 w-full" />)}
          </div>
        ) : (
          <>
            {/* YES levels */}
            <div className="py-1">
              {yesLevels.slice(0, 8).map((level, i) => {
                const fill = (parseFloat(level.depth) / maxYesDepth) * 100
                return (
                  <div key={i} className="relative px-3 py-1 text-xs">
                    <div
                      className="absolute inset-y-0 right-0 bg-yes/10"
                      style={{ width: `${fill}%` }}
                    />
                    <div className="relative flex justify-between">
                      <span className="text-yes font-medium">{level.price}</span>
                      <span className="text-[#888888]">{level.depth}</span>
                    </div>
                  </div>
                )
              })}
            </div>

            {/* Mid price */}
            <div className="flex items-center justify-between px-3 py-1.5 bg-[#f0f2f5] border-y border-[#e8e8e8] text-xs">
              <span className="text-[#888888]">{t('events.midPrice')}</span>
              <div className="flex gap-2">
                <span className="font-bold text-yes">{midPrice}</span>
                <span className="text-[#888888]">/</span>
                <span className="font-bold text-no">{midNo}</span>
              </div>
            </div>

            {/* NO levels */}
            <div className="py-1">
              {noLevels.slice(0, 8).map((level, i) => {
                const fill = (parseFloat(level.depth) / maxNoDepth) * 100
                return (
                  <div key={i} className="relative px-3 py-1 text-xs">
                    <div
                      className="absolute inset-y-0 right-0 bg-no/10"
                      style={{ width: `${fill}%` }}
                    />
                    <div className="relative flex justify-between">
                      <span className="text-no font-medium">{level.price}</span>
                      <span className="text-[#888888]">{level.depth}</span>
                    </div>
                  </div>
                )
              })}
            </div>
          </>
        )}
      </div>
    </div>
  )
}

// ─── Order Form ──────────────────────────────────────────────────────────────

function OrderForm({ event }: { event: Event }) {
  const { t } = useTranslation()
  const queryClient = useQueryClient()

  const [side, setSide] = useState<Side>('yes')
  const [price, setPrice] = useState(event.yes_initial_price)
  const [quantity, setQuantity] = useState('1')
  const [successMsg, setSuccessMsg] = useState(false)

  const priceNum = parseFloat(price) || 0
  const qtyNum = parseFloat(quantity) || 0
  const cost = priceNum * qtyNum
  const potentialProfit = qtyNum - cost
  const returnRate = cost > 0 ? (potentialProfit / cost) * 100 : 0

  function stepPrice(delta: number) {
    const cents = Math.round(parseFloat(price) * 100) + delta
    const clamped = Math.max(1, Math.min(99, cents))
    setPrice((clamped / 100).toFixed(2))
  }

  const { mutate, isPending, error } = useMutation({
    mutationFn: (payload: CreateOrderPayload) => createOrder(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['orderbook', event.ulid] })
      queryClient.invalidateQueries({ queryKey: ['trades', event.ulid] })
      queryClient.invalidateQueries({ queryKey: ['me'] })
      setQuantity('1')
      setSuccessMsg(true)
      setTimeout(() => setSuccessMsg(false), 3000)
    },
  })

  function handleSubmit() {
    if (qtyNum <= 0 || priceNum <= 0) return
    mutate({ event_ulid: event.ulid, side, price, quantity })
  }

  const isOpen = event.status === 'Open'

  return (
    <div className="flex flex-col bg-white overflow-y-auto w-full h-full">
      <div className="p-4 space-y-4">
        {/* YES / NO tabs */}
        <div className="grid grid-cols-2 gap-1 bg-[#f0f2f5] p-1 rounded-lg">
          <SideTab active={side === 'yes'} side="yes" onClick={() => setSide('yes')} label={t('common.yes')} />
          <SideTab active={side === 'no'} side="no" onClick={() => setSide('no')} label={t('common.no')} />
        </div>

        {/* Price stepper */}
        <div>
          <p className="text-xs font-medium text-[#888888] mb-1.5">{t('events.price')}</p>
          <div className="flex items-center gap-2">
            <button
              onClick={() => stepPrice(-1)}
              disabled={!isOpen}
              className="w-11 h-11 rounded-lg border border-[#e8e8e8] flex items-center justify-center text-dark hover:bg-[#f0f2f5] disabled:opacity-40 transition-colors"
            >
              <Minus size={14} />
            </button>
            <input
              type="number"
              min="0.01"
              max="0.99"
              step="0.01"
              value={price}
              onChange={(e) => {
                const v = Math.max(0.01, Math.min(0.99, parseFloat(e.target.value) || 0.01))
                setPrice(v.toFixed(2))
              }}
              disabled={!isOpen}
              className="flex-1 text-center font-bold text-lg text-dark border border-[#e8e8e8] rounded-lg py-2 focus:outline-none focus:border-primary disabled:opacity-40"
            />
            <button
              onClick={() => stepPrice(1)}
              disabled={!isOpen}
              className="w-11 h-11 rounded-lg border border-[#e8e8e8] flex items-center justify-center text-dark hover:bg-[#f0f2f5] disabled:opacity-40 transition-colors"
            >
              <Plus size={14} />
            </button>
          </div>
        </div>

        {/* Quantity */}
        <div>
          <Input
            label={t('events.quantity')}
            type="number"
            min="1"
            step="1"
            value={quantity}
            onChange={(e) => setQuantity(e.target.value)}
            disabled={!isOpen}
          />
        </div>

        {/* Summary */}
        <div className="bg-[#f0f2f5] rounded-lg p-3 space-y-2 text-sm">
          <SummaryRow label={t('events.cost')} value={`${cost.toFixed(2)} USDT`} />
          <SummaryRow
            label={t('events.potentialProfit')}
            value={`${potentialProfit.toFixed(2)} USDT`}
            valueClass={potentialProfit >= 0 ? 'text-yes' : 'text-no'}
          />
          <SummaryRow
            label={t('events.returnRate')}
            value={`${returnRate.toFixed(1)}%`}
            valueClass="text-primary"
          />
        </div>

        {/* Error */}
        {error && (
          <div className="flex items-center gap-2 bg-no-bg border border-no/30 text-no rounded-lg px-3 py-2 text-xs">
            <AlertCircle size={14} className="shrink-0" />
            <span>{error.message}</span>
          </div>
        )}

        {/* Success */}
        {successMsg && (
          <div className="flex items-center gap-2 bg-yes-bg border border-yes/30 text-yes rounded-lg px-3 py-2 text-xs">
            <CheckCircle size={14} className="shrink-0" />
            <span>سفارش با موفقیت ثبت شد</span>
          </div>
        )}

        {/* Submit */}
        <Button
          fullWidth
          variant={side === 'yes' ? 'primary' : 'danger'}
          loading={isPending}
          disabled={!isOpen || qtyNum <= 0}
          onClick={handleSubmit}
        >
          {isOpen
            ? `${t('events.placeOrder')} — ${side === 'yes' ? t('common.yes') : t('common.no')}`
            : t('status.closed')}
        </Button>

        {!isOpen && (
          <p className="text-center text-xs text-[#888888]">این ایونت باز نیست</p>
        )}
      </div>
    </div>
  )
}

function SideTab({
  active,
  side,
  onClick,
  label,
}: {
  active: boolean
  side: Side
  onClick: () => void
  label: string
}) {
  return (
    <button
      onClick={onClick}
      className={[
        'py-2 rounded-md text-sm font-semibold transition-colors min-h-[44px]',
        active
          ? side === 'yes'
            ? 'bg-yes text-white'
            : 'bg-no text-white'
          : 'text-[#888888] hover:text-dark',
      ].join(' ')}
    >
      {label}
    </button>
  )
}

function SummaryRow({
  label,
  value,
  valueClass = 'text-dark',
}: {
  label: string
  value: string
  valueClass?: string
}) {
  return (
    <div className="flex justify-between">
      <span className="text-[#888888]">{label}</span>
      <span className={['font-medium', valueClass].join(' ')}>{value}</span>
    </div>
  )
}

// ─── Recent Trades ───────────────────────────────────────────────────────────

function RecentTrades({ trades, loading }: { trades: Trade[]; loading: boolean }) {
  const { t } = useTranslation()

  return (
    <div className="flex flex-col bg-white overflow-hidden w-full h-full">
      <div className="grid grid-cols-3 px-3 py-2 text-xs text-[#888888] font-medium border-b border-[#e8e8e8] shrink-0">
        <span>{t('trades.quantity')}</span>
        <span className="text-center">{t('trades.price')}</span>
        <span className="text-left">{t('trades.time')}</span>
      </div>

      <div className="flex-1 overflow-y-auto">
        {loading ? (
          <div className="p-3 space-y-1.5">
            {Array.from({ length: 8 }).map((_, i) => <Skeleton key={i} className="h-5 w-full" />)}
          </div>
        ) : trades.length === 0 ? (
          <p className="p-4 text-center text-sm text-[#888888]">{t('common.noData')}</p>
        ) : (
          trades.map((trade) => (
            <div
              key={trade.ulid}
              className="grid grid-cols-3 px-3 py-1.5 text-xs border-b border-[#f0f2f5] hover:bg-[#f0f2f5] transition-colors"
            >
              <span className={trade.side === 'yes' ? 'text-yes' : 'text-no'}>
                {trade.quantity}
              </span>
              <span className={['text-center font-medium', trade.side === 'yes' ? 'text-yes' : 'text-no'].join(' ')}>
                {trade.price}
              </span>
              <span className="text-left text-[#888888]">{timeAgo(trade.created_at)}</span>
            </div>
          ))
        )}
      </div>
    </div>
  )
}
