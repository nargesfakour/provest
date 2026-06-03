import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Plus, Pencil, Trash2, AlertCircle } from 'lucide-react'

import {
  getAdminCategories,
  getSubCategories,
  createCategory,
  updateCategory,
  deleteCategory,
  createSubCategory,
  updateSubCategory,
  deleteSubCategory,
} from '@/api/adminCategories'
import Card, { CardHeader } from '@/components/ui/Card'
import Button from '@/components/ui/Button'
import Input from '@/components/ui/Input'
import Modal from '@/components/ui/Modal'
import Skeleton from '@/components/ui/Skeleton'
import type { Category, SubCategory } from '@/types/domain'

// ─── Schemas ─────────────────────────────────────────────────────────────────

const categorySchema = z.object({
  name: z.string().min(1, 'نام الزامی است'),
  slug: z.string().optional(),
})

const subCategorySchema = z.object({
  name: z.string().min(1, 'نام الزامی است'),
  slug: z.string().optional(),
})

type CategoryForm = z.infer<typeof categorySchema>
type SubCategoryForm = z.infer<typeof subCategorySchema>

// ─── Toggle ──────────────────────────────────────────────────────────────────

interface ToggleProps {
  checked: boolean
  onChange: () => void
  loading?: boolean
}

function Toggle({ checked, onChange, loading }: ToggleProps) {
  return (
    <button
      type="button"
      onClick={onChange}
      disabled={loading}
      className={[
        'relative inline-flex h-5 w-9 items-center rounded-full transition-colors shrink-0',
        checked ? 'bg-primary' : 'bg-[#e8e8e8]',
        loading ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
      ].join(' ')}
    >
      <span
        className={[
          'inline-block h-3.5 w-3.5 rounded-full bg-white shadow transition-transform',
          checked ? 'translate-x-4' : 'translate-x-0.5',
        ].join(' ')}
      />
    </button>
  )
}

// ─── Category CRUD panel ──────────────────────────────────────────────────────

interface CategoryPanelProps {
  categories: Category[]
  loading: boolean
  selectedUlid: string | null
  onSelect: (ulid: string) => void
}

function CategoryPanel({ categories, loading, selectedUlid, onSelect }: CategoryPanelProps) {
  const qc = useQueryClient()
  const [modal, setModal] = useState<{ mode: 'create' | 'edit'; data?: Category } | null>(null)
  const [apiError, setApiError] = useState('')
  const [togglePending, setTogglePending] = useState<string | null>(null)

  const { register, handleSubmit, reset, formState: { errors } } = useForm<CategoryForm>({
    resolver: zodResolver(categorySchema),
  })

  const createMut = useMutation({
    mutationFn: createCategory,
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['admin-categories'] }); closeModal() },
    onError: (e: Error) => setApiError(e.message),
  })

  const updateMut = useMutation({
    mutationFn: ({ ulid, payload }: { ulid: string; payload: Partial<CategoryForm> }) =>
      updateCategory(ulid, payload),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['admin-categories'] }); closeModal() },
    onError: (e: Error) => setApiError(e.message),
  })

  const deleteMut = useMutation({
    mutationFn: deleteCategory,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-categories'] }),
  })

  function openCreate() {
    reset({ name: '', slug: '' })
    setApiError('')
    setModal({ mode: 'create' })
  }

  function openEdit(cat: Category) {
    reset({ name: cat.name, slug: cat.slug })
    setApiError('')
    setModal({ mode: 'edit', data: cat })
  }

  function closeModal() { setModal(null); setApiError('') }

  async function onSubmit(values: CategoryForm) {
    if (modal?.mode === 'create') {
      createMut.mutate(values)
    } else if (modal?.data) {
      updateMut.mutate({ ulid: modal.data.ulid, payload: values })
    }
  }

  async function handleToggle(cat: Category) {
    setTogglePending(cat.ulid)
    try {
      await updateCategory(cat.ulid, { is_active: !cat.is_active })
      qc.invalidateQueries({ queryKey: ['admin-categories'] })
    } finally {
      setTogglePending(null)
    }
  }

  const isPending = createMut.isPending || updateMut.isPending

  return (
    <>
      <Card>
        <CardHeader
          title="دسته‌بندی‌ها"
          action={<Button size="sm" onClick={openCreate}><Plus size={14} /> افزودن</Button>}
        />

        {loading ? (
          <div className="space-y-3">
            {Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} className="h-10 w-full" />)}
          </div>
        ) : categories.length === 0 ? (
          <p className="text-sm text-[#888888] text-center py-6">دسته‌ای وجود ندارد</p>
        ) : (
          <div className="space-y-1">
            {categories.map((cat) => (
              <div
                key={cat.ulid}
                onClick={() => onSelect(cat.ulid)}
                className={[
                  'flex items-center justify-between px-3 py-2.5 rounded-lg cursor-pointer transition-colors',
                  selectedUlid === cat.ulid
                    ? 'bg-primary/10 border border-primary/20'
                    : 'hover:bg-[#f0f2f5] border border-transparent',
                ].join(' ')}
              >
                <div className="flex items-center gap-3 min-w-0">
                  <Toggle
                    checked={cat.is_active}
                    onChange={() => handleToggle(cat)}
                    loading={togglePending === cat.ulid}
                  />
                  <div className="min-w-0">
                    <p className="text-sm font-medium text-dark truncate">{cat.name}</p>
                    <p className="text-xs text-[#888888]">{cat.slug}</p>
                  </div>
                </div>
                <div className="flex items-center gap-1 shrink-0 mr-2" onClick={(e) => e.stopPropagation()}>
                  <button
                    onClick={() => openEdit(cat)}
                    className="p-1.5 rounded-lg text-[#888888] hover:text-primary hover:bg-primary/10 transition-colors"
                  >
                    <Pencil size={14} />
                  </button>
                  <button
                    onClick={() => deleteMut.mutate(cat.ulid)}
                    className="p-1.5 rounded-lg text-[#888888] hover:text-no hover:bg-no-bg transition-colors"
                  >
                    <Trash2 size={14} />
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </Card>

      <Modal
        isOpen={modal !== null}
        onClose={closeModal}
        title={modal?.mode === 'create' ? 'افزودن دسته‌بندی' : 'ویرایش دسته‌بندی'}
        size="sm"
      >
        {apiError && (
          <div className="flex items-center gap-2 bg-no-bg border border-no/30 text-no rounded-lg px-3 py-2 mb-4 text-sm">
            <AlertCircle size={15} />
            <span>{apiError}</span>
          </div>
        )}
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          <Input label="نام" placeholder="مثال: ورزش" error={errors.name?.message} {...register('name')} />
          <Input label="اسلاگ" placeholder="اختیاری — از نام تولید می‌شود" {...register('slug')} />
          <div className="flex gap-3 justify-end">
            <Button type="button" variant="secondary" onClick={closeModal}>انصراف</Button>
            <Button type="submit" loading={isPending}>
              {modal?.mode === 'create' ? 'افزودن' : 'ذخیره'}
            </Button>
          </div>
        </form>
      </Modal>
    </>
  )
}

// ─── SubCategory CRUD panel ───────────────────────────────────────────────────

interface SubCategoryPanelProps {
  category: Category | null
}

function SubCategoryPanel({ category }: SubCategoryPanelProps) {
  const qc = useQueryClient()
  const [modal, setModal] = useState<{ mode: 'create' | 'edit'; data?: SubCategory } | null>(null)
  const [apiError, setApiError] = useState('')
  const [togglePending, setTogglePending] = useState<string | null>(null)

  const { register, handleSubmit, reset, formState: { errors } } = useForm<SubCategoryForm>({
    resolver: zodResolver(subCategorySchema),
  })

  const { data: subs = [], isLoading: subsLoading } = useQuery({
    queryKey: ['admin-sub-categories', category?.ulid],
    queryFn: () => getSubCategories(category!.ulid),
    enabled: !!category,
    staleTime: 30_000,
  })

  function invalidateSubs() {
    qc.invalidateQueries({ queryKey: ['admin-sub-categories', category?.ulid] })
  }

  const createMut = useMutation({
    mutationFn: (values: SubCategoryForm) =>
      createSubCategory(category!.ulid, values),
    onSuccess: () => { invalidateSubs(); closeModal() },
    onError: (e: Error) => setApiError(e.message),
  })

  const updateMut = useMutation({
    mutationFn: ({ ulid, payload }: { ulid: string; payload: Partial<SubCategoryForm> }) =>
      updateSubCategory(ulid, payload),
    onSuccess: () => { invalidateSubs(); closeModal() },
    onError: (e: Error) => setApiError(e.message),
  })

  const deleteMut = useMutation({
    mutationFn: deleteSubCategory,
    onSuccess: () => invalidateSubs(),
  })

  function openCreate() {
    reset({ name: '', slug: '' })
    setApiError('')
    setModal({ mode: 'create' })
  }

  function openEdit(sub: SubCategory) {
    reset({ name: sub.name, slug: sub.slug })
    setApiError('')
    setModal({ mode: 'edit', data: sub })
  }

  function closeModal() { setModal(null); setApiError('') }

  async function onSubmit(values: SubCategoryForm) {
    if (modal?.mode === 'create') createMut.mutate(values)
    else if (modal?.data) updateMut.mutate({ ulid: modal.data.ulid, payload: values })
  }

  async function handleToggle(sub: SubCategory) {
    setTogglePending(sub.ulid)
    try {
      await updateSubCategory(sub.ulid, { is_active: !sub.is_active })
      invalidateSubs()
    } finally {
      setTogglePending(null)
    }
  }

  const isPending = createMut.isPending || updateMut.isPending

  return (
    <>
      <Card>
        <CardHeader
          title={category ? `زیر‌دسته‌های "${category.name}"` : 'زیر‌دسته‌بندی‌ها'}
          action={
            category && (
              <Button size="sm" onClick={openCreate}><Plus size={14} /> افزودن</Button>
            )
          }
        />

        {!category ? (
          <p className="text-sm text-[#888888] text-center py-10">یک دسته‌بندی را انتخاب کنید</p>
        ) : subsLoading ? (
          <div className="space-y-3">
            {Array.from({ length: 3 }).map((_, i) => <Skeleton key={i} className="h-10 w-full" />)}
          </div>
        ) : subs.length === 0 ? (
          <p className="text-sm text-[#888888] text-center py-6">زیر‌دسته‌ای وجود ندارد</p>
        ) : (
          <div className="space-y-1">
            {subs.map((sub) => (
              <div
                key={sub.ulid}
                className="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-[#f0f2f5] transition-colors"
              >
                <div className="flex items-center gap-3 min-w-0">
                  <Toggle
                    checked={sub.is_active}
                    onChange={() => handleToggle(sub)}
                    loading={togglePending === sub.ulid}
                  />
                  <div className="min-w-0">
                    <p className="text-sm font-medium text-dark truncate">{sub.name}</p>
                    <p className="text-xs text-[#888888]">{sub.slug}</p>
                  </div>
                </div>
                <div className="flex items-center gap-1 shrink-0 mr-2">
                  <button
                    onClick={() => openEdit(sub)}
                    className="p-1.5 rounded-lg text-[#888888] hover:text-primary hover:bg-primary/10 transition-colors"
                  >
                    <Pencil size={14} />
                  </button>
                  <button
                    onClick={() => deleteMut.mutate(sub.ulid)}
                    className="p-1.5 rounded-lg text-[#888888] hover:text-no hover:bg-no-bg transition-colors"
                  >
                    <Trash2 size={14} />
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </Card>

      <Modal
        isOpen={modal !== null}
        onClose={closeModal}
        title={modal?.mode === 'create' ? 'افزودن زیر‌دسته' : 'ویرایش زیر‌دسته'}
        size="sm"
      >
        {apiError && (
          <div className="flex items-center gap-2 bg-no-bg border border-no/30 text-no rounded-lg px-3 py-2 mb-4 text-sm">
            <AlertCircle size={15} />
            <span>{apiError}</span>
          </div>
        )}
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          <Input label="نام" placeholder="مثال: فوتبال" error={errors.name?.message} {...register('name')} />
          <Input label="اسلاگ" placeholder="اختیاری" {...register('slug')} />
          <div className="flex gap-3 justify-end">
            <Button type="button" variant="secondary" onClick={closeModal}>انصراف</Button>
            <Button type="submit" loading={isPending}>
              {modal?.mode === 'create' ? 'افزودن' : 'ذخیره'}
            </Button>
          </div>
        </form>
      </Modal>
    </>
  )
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function AdminCategoriesPage() {
  const [selectedUlid, setSelectedUlid] = useState<string | null>(null)

  const { data: categories = [], isLoading } = useQuery({
    queryKey: ['admin-categories'],
    queryFn: getAdminCategories,
    staleTime: 60_000,
  })

  const selectedCategory = categories.find((c) => c.ulid === selectedUlid) ?? null

  return (
    <div className="p-4 md:p-6">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
        <CategoryPanel
          categories={categories}
          loading={isLoading}
          selectedUlid={selectedUlid}
          onSelect={setSelectedUlid}
        />
        <SubCategoryPanel category={selectedCategory} />
      </div>
    </div>
  )
}
