import client from './client'
import type { ApiResponse } from '@/types/api'
import type { Position } from '@/types/domain'

export async function getPositions(): Promise<Position[]> {
  const res = await client.get<ApiResponse<Position[]>>('/positions')
  return res.data.data
}
