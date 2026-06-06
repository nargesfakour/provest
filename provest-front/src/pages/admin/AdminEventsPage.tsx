import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Plus, Eye, Pencil, AlertCircle, Search } from 'lucide-react'

import {
  getAdminEvents,
  createEvent,
  updateEvent,
  openEvent,
  closeEvent,
  settleEvent,
  type CreateEventPayload,
} from '@/api/adminEvents'
import { getAdminCategories, getSubCategories } from '@/api/adminCategories'
import Button from '@/components/ui/Button'
import Input from '@/components/ui/Input'
import Badge from '@/components/ui/Badge'
import Modal from '@/components/ui/Modal'
import Pagination from '@/components/ui/Pagination'
import Skeleton from '@/components/ui/Skeleton'
import { formatDate } from '@/utils/date'
import type { Event } from '@/types/domain'

// ─── Constants ───────────────────────────────────────────────────────────────

const statusOptions: { value: string; label: string }[] = [
  { value: '', label: 'همه وضعیت‌ها' },
  { value: 'pending', label: 'در انتظار' },
  { value: 'open', label: 'باز' },
  { value: 'closed', label: 'بسته' },
  { value: 'settled', label: 'تسویه' },
]

const statusBadge: Record<string, { variant: 'open' | 'closed' | 'settled' | 'pending' | 'default'; label: string }> = {
  Open:    { variant: 'open',    label: 'باز' },
  Closed:  { variant: 'closed',  label: 'بسته' },
  Settled: { variant: 'settled', label: 'تسویه' },
  Pending: { variant: 'pending', label: 'در انتظار' },
}

const fallbackBadge = { variant: 'default' as const, label: '—' }

// ─── Create / Edit schema ─────────────────────────────────────────────────────

const eventSchema = z.object({
  title: z.string().min(1, 'عنوان الزامی است'),
  description: z.string().optional(),
  sub_category_ulid: z.string().min(1, 'زیر‌دسته الزامی است'),
  yes_initial_price: z.string().regex(/^0\.(0[1-9]|[1-9]\d?)$|^0\.99$/, 'قیمت باید بین ۰.۰۱ و ۰.۹۹ باشد'),
  fee_rate: z.string().min(1, 'کارمزد الزامی است'),
  starts_at: z.string().optional(),
  ends_at: z.string().min(1, 'تاریخ پایان الزامی است'),
  resolve_at: z.string().optional(),
})

type EventForm = z.infer<typeof eventSchema>

// ─── Price derived from slider value ─────────────────────────────────────────

function toPriceStr(intVal: number): string {
  return (intVal / 100).toFixed(2)
}

// ─── Create / Edit modal ──────────────────────────────────────────────────────

interface EventModalProps {
  isOpen: boolean
  onClose: () => void
  editing: Event | null
}

function EventModal({ isOpen, onClose, editing }: EventModalProps) {
  const qc = useQueryClient()
  const [apiError, setApiError] = useState('')
  const [yesSlider, setYesSlider] = useState(65)
  const [selectedCategoryUlid, setSelectedCategoryUlid] = useState('')

  const { data: categories = [] } = useQuery({
    queryKey: ['admin-categories'],
    queryFn: getAdminCategories,
    staleTime: 60_000,
  })

  const { data: subCategories = [] } = useQuery({
    queryKey: ['admin-sub-categories', selectedCategoryUlid],
    queryFn: () => getSubCategories(selectedCategoryUlid),
    enabled: !!selectedCategoryUlid,
    staleTime: 30_000,
  })

  const {
    register,
    handleSubmit,
    setValue,
    reset,
    formState: { errors },
  } = useForm<EventForm>({
    resolver: zodResolver(eventSchema),
    defaultValues: {
      yes_initial_price: toPriceStr(yesSlider),
      fee_rate: '0.01',
    },
  })

  // Sync slider → form field
  function handleSlider(val: number) {
    setYesSlider(val)
    setValue('yes_initial_price', toPriceStr(val), { shouldValidate: true })
  }

  // When modal opens for editing, populate fields
  useState(() => {
    if (editing) {
      const parsed = Math.round(parseFloat(editing.yes_initial_price) * 100)
      setYesSlider(parsed)
      reset({
        title: editing.title,
        description: editing.description ?? '',
        sub_category_ulid: '',
        yes_initial_price: editing.yes_initial_price,
        fee_rate: editing.fee_rate,
        starts_at: editing.starts_at?.slice(0, 16) ?? '',
        ends_at: editing.ends_at?.slice(0, 16) ?? '',
        resolve_at: editing.resolve_at?.slice(0, 16) ?? '',
      })
    } else {
      setYesSlider(65)
      reset({ yes_initial_price: '0.65', fee_rate: '0.01' })
    }
  })

  const createMut = useMutation({
    mutationFn: (payload: CreateEventPayload) => createEvent(payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin-events'] })
      onClose()
    },
    onError: (e: Error) => setApiError(e.message),
  })

  const updateMut = useMutation({
    mutationFn: (payload: CreateEventPayload) => updateEvent(editing!.ulid, payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin-events'] })
      onClose()
    },
    onError: (e: Error) => setApiError(e.message),
  })

  function onSubmit(values: EventForm) {
    setApiError('')
    const payload: CreateEventPayload = {
      title: values.title,
      description: values.description || undefined,
      sub_category_ulid: values.sub_category_ulid,
      yes_initial_price: values.yes_initial_price,
      fee_rate: values.fee_rate,
      starts_at: values.starts_at || undefined,
      ends_at: values.ends_at,
      resolve_at: values.resolve_at || undefined,
    }
    if (editing) updateMut.mutate(payload)
    else createMut.mutate(payload)
  }

  const isPending = createMut.isPending || updateMut.isPending
  const noPrice = (1 - yesSlider / 100).toFixed(2)

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={editing ? 'ویرایش ایونت' : 'ایونت جدید'}
      size="lg"
    >
      {apiError && (
        <div className="flex items-center gap-2 bg-no-bg border border-no/30 text-no rounded-lg px-3 py-2 mb-4 text-sm">
          <AlertCircle size={15} className="shrink-0" />
          <span>{apiError}</span>
        </div>
      )}

      <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
        {/* Title */}
        <Input
          label="عنوان"
          placeholder="سوال پیش‌بینی را وارد کنید"
          error={errors.title?.message}
          {...register('title')}
        />

        {/* Description */}
        <div className="flex flex-col gap-1">
          <label className="text-sm font-medium text-dark">توضیحات (اختیاری)</label>
          <textarea
            className="w-full rounded-lg border border-[#e8e8e8] bg-white px-3 py-2 text-sm text-dark placeholder:text-[#bbbbbb] focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors resize-none"
            rows={2}
            placeholder="توضیح بیشتر..."
            {...register('description')}
          />
        </div>

        {/* Category + Sub-category */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div className="flex flex-col gap-1">
            <label className="text-sm font-medium text-dark">دسته‌بندی</label>
            <select
              className="w-full rounded-lg border border-[#e8e8e8] bg-white px-3 py-2 text-sm text-dark focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
              value={selectedCategoryUlid}
              onChange={(e) => {
                setSelectedCategoryUlid(e.target.value)
                setValue('sub_category_ulid', '', { shouldValidate: false })
              }}
            >
              <option value="">انتخاب دسته...</option>
              {categories.map((cat) => (
                <option key={cat.ulid} value={cat.ulid}>{cat.name}</option>
              ))}
            </select>
          </div>

          <div className="flex flex-col gap-1">
            <label className="text-sm font-medium text-dark">زیر‌دسته</label>
            <select
              className={[
                'w-full rounded-lg border bg-white px-3 py-2 text-sm text-dark focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors',
                errors.sub_category_ulid ? 'border-no' : 'border-[#e8e8e8]',
              ].join(' ')}
              disabled={!selectedCategoryUlid}
              {...register('sub_category_ulid')}
            >
              <option value="">
                {selectedCategoryUlid ? 'انتخاب زیر‌دسته...' : 'ابتدا دسته انتخاب کنید'}
              </option>
              {subCategories.map((sub) => (
                <option key={sub.ulid} value={sub.ulid}>{sub.name}</option>
              ))}
            </select>
            {errors.sub_category_ulid && (
              <p className="text-xs text-no">{errors.sub_category_ulid.message}</p>
            )}
          </div>
        </div>

        {/* YES / NO price slider */}
        <div className="flex flex-col gap-2">
          <label className="text-sm font-medium text-dark">قیمت اولیه YES</label>
          <div className="flex items-center gap-4">
            <input
              type="range"
              min={1}
              max={99}
              step={1}
              value={yesSlider}
              onChange={(e) => handleSlider(parseInt(e.target.value))}
              className="flex-1 accent-primary"
            />
            <div className="flex gap-2 shrink-0">
              <span className="inline-flex items-center gap-1 bg-yes-bg text-yes text-sm font-bold px-2.5 py-1 rounded-lg">
                YES {toPriceStr(yesSlider)}
              </span>
              <span className="inline-flex items-center gap-1 bg-no-bg text-no text-sm font-bold px-2.5 py-1 rounded-lg">
                NO {noPrice}
              </span>
            </div>
          </div>
          {errors.yes_initial_price && (
            <p className="text-xs text-no">{errors.yes_initial_price.message}</p>
          )}
        </div>

        {/* Fee rate + dates in grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <Input
            label="نرخ کارمزد (مثال: 0.01)"
            placeholder="0.01"
            error={errors.fee_rate?.message}
            {...register('fee_rate')}
          />
          <Input
            label="شروع (اختیاری)"
            type="datetime-local"
            {...register('starts_at')}
          />
          <Input
            label="پایان"
            type="datetime-local"
            error={errors.ends_at?.message}
            {...register('ends_at')}
          />
          <Input
            label="تسویه (اختیاری)"
            type="datetime-local"
            {...register('resolve_at')}
          />
        </div>

        <div className="flex gap-3 justify-end pt-2">
          <Button type="button" variant="secondary" onClick={onClose}>انصراف</Button>
          <Button type="submit" loading={isPending}>
            {editing ? 'ذخیره تغییرات' : 'ایجاد ایونت'}
          </Button>
        </div>
      </form>
    </Modal>
  )
}

// ─── Settle modal ─────────────────────────────────────────────────────────────

interface SettleModalProps {
  isOpen: boolean
  onClose: () => void
  event: Event | null
}

function SettleModal({ isOpen, onClose, event }: SettleModalProps) {
  const qc = useQueryClient()
  const [outcome, setOutcome] = useState<'yes' | 'no' | null>(null)
  const [apiError, setApiError] = useState('')

  const settleMut = useMutation({
    mutationFn: () => settleEvent(event!.ulid, outcome!),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin-events'] })
      onClose()
    },
    onError: (e: Error) => setApiError(e.message),
  })

  return (
    <Modal isOpen={isOpen} onClose={onClose} title="تسویه ایونت" size="sm">
      {apiError && (
        <div className="flex items-center gap-2 bg-no-bg border border-no/30 text-no rounded-lg px-3 py-2 mb-4 text-sm">
          <AlertCircle size={15} />
          <span>{apiError}</span>
        </div>
      )}
      <p className="text-sm text-[#888888] mb-4">
        نتیجه ایونت «{event?.title}» را انتخاب کنید:
      </p>
      <div className="grid grid-cols-2 gap-3 mb-6">
        <button
          type="button"
          onClick={() => setOutcome('yes')}
          className={[
            'py-3 rounded-xl border-2 font-bold text-lg transition-colors',
            outcome === 'yes'
              ? 'border-yes bg-yes-bg text-yes'
              : 'border-[#e8e8e8] text-[#888888] hover:border-yes hover:text-yes',
          ].join(' ')}
        >
          YES ✓
        </button>
        <button
          type="button"
          onClick={() => setOutcome('no')}
          className={[
            'py-3 rounded-xl border-2 font-bold text-lg transition-colors',
            outcome === 'no'
              ? 'border-no bg-no-bg text-no'
              : 'border-[#e8e8e8] text-[#888888] hover:border-no hover:text-no',
          ].join(' ')}
        >
          NO ✗
        </button>
      </div>
      <div className="flex gap-3 justify-end">
        <Button variant="secondary" onClick={onClose}>انصراف</Button>
        <Button
          disabled={!outcome}
          loading={settleMut.isPending}
          onClick={() => settleMut.mutate()}
        >
          تأیید تسویه
        </Button>
      </div>
    </Modal>
  )
}

// ─── Status action buttons ────────────────────────────────────────────────────

interface ActionButtonsProps {
  event: Event
  onEdit: () => void
  onSettle: () => void
}

function ActionButtons({ event, onEdit, onSettle }: ActionButtonsProps) {
  const qc = useQueryClient()

  const openMut = useMutation({
    mutationFn: () => openEvent(event.ulid),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-events'] }),
  })

  const closeMut = useMutation({
    mutationFn: () => closeEvent(event.ulid),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-events'] }),
  })

  return (
    <div className="flex items-center gap-1.5">
      <button
        title="مشاهده"
        className="p-1.5 rounded-lg text-[#888888] hover:text-primary hover:bg-primary/10 transition-colors"
        onClick={(e) => { e.stopPropagation() }}
      >
        <Eye size={15} />
      </button>

      {event.status === 'Pending' && (
        <>
          <button
            title="ویرایش"
            onClick={(e) => { e.stopPropagation(); onEdit() }}
            className="p-1.5 rounded-lg text-[#888888] hover:text-primary hover:bg-primary/10 transition-colors"
          >
            <Pencil size={15} />
          </button>
          <Button
            size="sm"
            variant="secondary"
            loading={openMut.isPending}
            onClick={(e) => { e.stopPropagation(); openMut.mutate() }}
          >
            باز کن
          </Button>
        </>
      )}

      {event.status === 'Open' && (
        <Button
          size="sm"
          variant="secondary"
          loading={closeMut.isPending}
          onClick={(e) => { e.stopPropagation(); closeMut.mutate() }}
        >
          ببند
        </Button>
      )}

      {event.status === 'Closed' && (
        <Button
          size="sm"
          onClick={(e) => { e.stopPropagation(); onSettle() }}
        >
          تسویه
        </Button>
      )}
    </div>
  )
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function AdminEventsPage() {
  const navigate = useNavigate()
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const [createOpen, setCreateOpen] = useState(false)
  const [editingEvent, setEditingEvent] = useState<Event | null>(null)
  const [settlingEvent, setSettlingEvent] = useState<Event | null>(null)

  const { data, isLoading } = useQuery({
    queryKey: ['admin-events', page, search, statusFilter],
    queryFn: () =>
      getAdminEvents({
        page,
        per_page: 15,
        search: search || undefined,
        status: statusFilter || undefined,
      }),
    staleTime: 30_000,
  })

  const events = data?.items ?? []
  const totalPages = data?.last_page ?? 1

  return (
    <div className="p-4 md:p-6 space-y-4">

      {/* ── Header ──────────────────────────────────────────────────── */}
      <div className="flex items-center justify-between">
        <h2 className="text-base md:text-lg font-bold text-dark">مدیریت ایونت‌ها</h2>
        <Button onClick={() => setCreateOpen(true)}>
          <Plus size={16} /> <span className="hidden sm:inline">ایونت جدید</span>
        </Button>
      </div>

      {/* ── Filters ─────────────────────────────────────────────────── */}
      <div className="bg-white border border-[#e8e8e8] rounded-xl p-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-3 sm:gap-4">
        <div className="flex-1">
          <Input
            placeholder="جستجو در عنوان..."
            rightIcon={<Search size={15} />}
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1) }}
          />
        </div>
        <select
          className="rounded-lg border border-[#e8e8e8] bg-white px-3 py-2 text-sm text-dark focus:outline-none focus:ring-2 focus:ring-primary w-full sm:w-auto"
          value={statusFilter}
          onChange={(e) => { setStatusFilter(e.target.value); setPage(1) }}
        >
          {statusOptions.map((o) => (
            <option key={o.value} value={o.value}>{o.label}</option>
          ))}
        </select>
      </div>

      {/* ── Table ───────────────────────────────────────────────────── */}
      <div className="bg-white border border-[#e8e8e8] rounded-xl overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-[#e8e8e8] bg-[#f8f9fa]">
              <th className="px-4 py-3 text-right font-medium text-[#888888]">عنوان</th>
              <th className="px-4 py-3 text-right font-medium text-[#888888] hidden md:table-cell">دسته</th>
              <th className="px-4 py-3 text-right font-medium text-[#888888] hidden md:table-cell">YES / NO</th>
              <th className="px-4 py-3 text-right font-medium text-[#888888] hidden lg:table-cell">کارمزد</th>
              <th className="px-4 py-3 text-right font-medium text-[#888888]">وضعیت</th>
              <th className="px-4 py-3 text-right font-medium text-[#888888] hidden md:table-cell">پایان</th>
              <th className="px-4 py-3 text-right font-medium text-[#888888]">عملیات</th>
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              Array.from({ length: 8 }).map((_, i) => (
                <tr key={i} className="border-b border-[#f0f2f5]">
                  <td className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
                  <td className="px-4 py-3 hidden md:table-cell"><Skeleton className="h-4 w-full" /></td>
                  <td className="px-4 py-3 hidden md:table-cell"><Skeleton className="h-4 w-full" /></td>
                  <td className="px-4 py-3 hidden lg:table-cell"><Skeleton className="h-4 w-full" /></td>
                  <td className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
                  <td className="px-4 py-3 hidden md:table-cell"><Skeleton className="h-4 w-full" /></td>
                  <td className="px-4 py-3"><Skeleton className="h-4 w-full" /></td>
                </tr>
              ))
            ) : events.length === 0 ? (
              <tr>
                <td colSpan={7} className="px-4 py-12 text-center text-[#888888]">
                  ایونتی یافت نشد
                </td>
              </tr>
            ) : (
              events.map((event) => (
                <tr
                  key={event.ulid}
                  onClick={() => navigate(`/admin/events/${event.ulid}`)}
                  className="border-b border-[#f0f2f5] hover:bg-[#f8f9fa] transition-colors cursor-pointer"
                >
                  <td className="px-4 py-3 font-medium text-dark max-w-[160px] md:max-w-[220px]">
                    <p className="truncate">{event.title}</p>
                  </td>
                  <td className="px-4 py-3 text-[#888888] text-xs hidden md:table-cell">{event.sub_category.name}</td>
                  <td className="px-4 py-3 hidden md:table-cell">
                    <div className="flex items-center gap-1.5">
                      <span className="text-yes font-medium">{event.yes_initial_price}</span>
                      <span className="text-[#bbbbbb]">/</span>
                      <span className="text-no font-medium">{event.no_initial_price}</span>
                    </div>
                  </td>
                  <td className="px-4 py-3 text-[#888888] hidden lg:table-cell">{event.fee_rate}</td>
                  <td className="px-4 py-3">
                    {(() => {
                      const b = statusBadge[event.status] ?? fallbackBadge
                      return <Badge variant={b.variant}>{b.label}</Badge>
                    })()}
                  </td>
                  <td className="px-4 py-3 text-[#888888] text-xs whitespace-nowrap hidden md:table-cell">
                    {event.ends_at ? formatDate(event.ends_at) : '—'}
                  </td>
                  <td className="px-4 py-3" onClick={(e) => e.stopPropagation()}>
                    <ActionButtons
                      event={event}
                      onEdit={() => setEditingEvent(event)}
                      onSettle={() => setSettlingEvent(event)}
                    />
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>

        <Pagination page={page} totalPages={totalPages} onChange={setPage} />
      </div>

      {/* ── Modals ──────────────────────────────────────────────────── */}
      <EventModal
        isOpen={createOpen || editingEvent !== null}
        onClose={() => { setCreateOpen(false); setEditingEvent(null) }}
        editing={editingEvent}
      />
      <SettleModal
        isOpen={settlingEvent !== null}
        onClose={() => setSettlingEvent(null)}
        event={settlingEvent}
      />
    </div>
  )
}
