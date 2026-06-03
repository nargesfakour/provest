import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { TrendingUp, Clock, ArrowLeft } from 'lucide-react'

import PublicLayout from '@/components/layout/PublicLayout'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import { SkeletonCard } from '@/components/ui/Skeleton'

import { getEvents, getCategories } from '@/api/events'
import { formatDate } from '@/utils/date'
import type { Event } from '@/types/domain'

export default function LandingPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [selectedCategory, setSelectedCategory] = useState<string>('')

  const { data: categoriesData } = useQuery({
    queryKey: ['public-categories'],
    queryFn: getCategories,
    staleTime: 300_000,
  })

  const { data: eventsData, isLoading, isError } = useQuery({
    queryKey: ['public-events', selectedCategory],
    queryFn: () =>
      getEvents({ status: 'open', category: selectedCategory || undefined, per_page: 6 }),
    staleTime: 30_000,
  })

  const events = eventsData?.items ?? []
  const categories = categoriesData ?? []

  return (
    <PublicLayout>
      {/* Hero */}
      <section className="bg-dark text-white py-20 px-6">
        <div className="max-w-6xl mx-auto text-center">
          <div className="inline-flex items-center gap-2 bg-primary/20 text-primary px-4 py-1.5 rounded-full text-sm font-medium mb-6">
            <TrendingUp size={14} />
            <span>{t('app.tagline')}</span>
          </div>
          <h1 className="text-4xl md:text-5xl font-bold mb-4 leading-tight">
            {t('landing.hero.title')}
          </h1>
          <p className="text-[#aabbcc] text-lg mb-8 max-w-xl mx-auto">
            {t('landing.hero.subtitle')}
          </p>
          <Button size="lg" onClick={() => navigate('/register')}>
            {t('landing.hero.cta')}
          </Button>
        </div>
      </section>

      {/* Events section */}
      <section className="max-w-6xl mx-auto px-6 py-12">
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-xl font-bold text-dark">{t('landing.eventsTitle')}</h2>
          <Link to="/app/events" className="flex items-center gap-1 text-sm text-primary hover:underline">
            <span>{t('landing.viewAll')}</span>
            <ArrowLeft size={14} />
          </Link>
        </div>

        {/* Category filter tabs */}
        {categories.length > 0 && (
          <div className="flex items-center gap-2 mb-6 overflow-x-auto pb-1">
            <CategoryTab
              label={t('common.all')}
              active={selectedCategory === ''}
              onClick={() => setSelectedCategory('')}
            />
            {categories.map((cat) => (
              <CategoryTab
                key={cat.ulid}
                label={cat.name}
                active={selectedCategory === cat.slug}
                onClick={() => setSelectedCategory(cat.slug)}
              />
            ))}
          </div>
        )}

        {/* Grid */}
        {isError ? (
          <p className="text-center text-[#888888] py-12">{t('common.error')}</p>
        ) : isLoading ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {Array.from({ length: 6 }).map((_, i) => <SkeletonCard key={i} />)}
          </div>
        ) : events.length === 0 ? (
          <p className="text-center text-[#888888] py-12">{t('events.noEvents')}</p>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {events.map((event) => <EventCard key={event.ulid} event={event} />)}
          </div>
        )}
      </section>
    </PublicLayout>
  )
}

// ─── Category Tab ────────────────────────────────────────────────────────────

interface CategoryTabProps {
  label: string
  active: boolean
  onClick: () => void
}

function CategoryTab({ label, active, onClick }: CategoryTabProps) {
  return (
    <button
      onClick={onClick}
      className={[
        'px-4 py-1.5 rounded-full text-sm font-medium whitespace-nowrap transition-colors',
        active
          ? 'bg-primary text-white'
          : 'bg-white border border-[#e8e8e8] text-[#888888] hover:border-primary hover:text-primary',
      ].join(' ')}
    >
      {label}
    </button>
  )
}

// ─── Event Card ──────────────────────────────────────────────────────────────

interface EventCardProps {
  event: Event
}

function EventCard({ event }: EventCardProps) {
  const { t } = useTranslation()
  const navigate = useNavigate()

  return (
    <div
      onClick={() => navigate('/app/events')}
      className="bg-white border border-[#e8e8e8] rounded-xl p-4 hover:border-primary hover:shadow-sm transition-all cursor-pointer group"
    >
      {/* Header */}
      <div className="flex items-start justify-between gap-2 mb-3">
        <h3 className="font-semibold text-dark text-sm leading-snug line-clamp-2 group-hover:text-primary transition-colors">
          {event.title}
        </h3>
        <Badge variant="open" className="shrink-0">{t('status.open')}</Badge>
      </div>

      {/* Category */}
      <p className="text-xs text-[#888888] mb-4">{event.sub_category.name}</p>

      {/* YES / NO prices */}
      <div className="grid grid-cols-2 gap-2 mb-4">
        <PriceBox side="yes" price={event.yes_initial_price} />
        <PriceBox side="no" price={event.no_initial_price} />
      </div>

      {/* Footer */}
      {event.ends_at && (
        <div className="flex items-center gap-1.5 text-xs text-[#888888]">
          <Clock size={12} />
          <span>{t('events.endDate')}: {formatDate(event.ends_at)}</span>
        </div>
      )}
    </div>
  )
}

interface PriceBoxProps {
  side: 'yes' | 'no'
  price: string
}

function PriceBox({ side, price }: PriceBoxProps) {
  const { t } = useTranslation()
  const isYes = side === 'yes'

  return (
    <div
      className={[
        'rounded-lg p-2 text-center',
        isYes ? 'bg-yes-bg' : 'bg-no-bg',
      ].join(' ')}
    >
      <p className={['text-xs font-medium mb-0.5', isYes ? 'text-yes' : 'text-no'].join(' ')}>
        {isYes ? t('common.yes') : t('common.no')}
      </p>
      <p className={['text-sm font-bold', isYes ? 'text-yes' : 'text-no'].join(' ')}>
        {price}
      </p>
    </div>
  )
}
