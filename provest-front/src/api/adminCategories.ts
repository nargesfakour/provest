import adminClient from './adminClient'
import type { ApiResponse } from '@/types/api'
import type { Category, SubCategory } from '@/types/domain'

export interface CategoryPayload {
  name: string
  slug?: string
  is_active?: boolean
}

export interface SubCategoryPayload {
  name: string
  slug?: string
  is_active?: boolean
}

export async function getAdminCategories(): Promise<Category[]> {
  const res = await adminClient.get<ApiResponse<Category[]>>('/categories')
  return res.data.data
}

export async function getSubCategories(categoryUlid: string): Promise<SubCategory[]> {
  const res = await adminClient.get<ApiResponse<SubCategory[]>>(`/categories/${categoryUlid}/sub-categories`)
  return res.data.data
}

export async function createCategory(payload: CategoryPayload): Promise<Category> {
  const res = await adminClient.post<ApiResponse<Category>>('/categories', payload)
  return res.data.data
}

export async function updateCategory(ulid: string, payload: Partial<CategoryPayload>): Promise<Category> {
  const res = await adminClient.put<ApiResponse<Category>>(`/categories/${ulid}`, payload)
  return res.data.data
}

export async function deleteCategory(ulid: string): Promise<void> {
  await adminClient.delete(`/categories/${ulid}`)
}

export async function createSubCategory(categoryUlid: string, payload: SubCategoryPayload): Promise<SubCategory> {
  const res = await adminClient.post<ApiResponse<SubCategory>>(`/categories/${categoryUlid}/sub-categories`, payload)
  return res.data.data
}

export async function updateSubCategory(ulid: string, payload: Partial<SubCategoryPayload>): Promise<SubCategory> {
  const res = await adminClient.put<ApiResponse<SubCategory>>(`/sub-categories/${ulid}`, payload)
  return res.data.data
}

export async function deleteSubCategory(ulid: string): Promise<void> {
  await adminClient.delete(`/sub-categories/${ulid}`)
}
