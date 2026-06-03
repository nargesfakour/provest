import client from './client'
import { mapPaginated } from '@/types/api'
import type { ApiResponse, LaravelPaginatedResponse, PaginatedData, SubmitDepositPayload, CreateWithdrawalPayload } from '@/types/api'
import type { WalletTransaction, Deposit, Withdrawal } from '@/types/domain'

export interface DepositInfo {
  address: string
  network: string
  coin: string
  min_amount: string
}

export interface WithdrawalFeeInfo {
  fee: string
  net_amount: string
}

export interface TransactionsParams {
  page?: number
  per_page?: number
  type?: string
}

export async function getWalletBalance(): Promise<{ balance: string }> {
  const res = await client.get<ApiResponse<{ balance: string }>>('/wallet/balance')
  return res.data.data
}

export async function getTransactions(
  params: TransactionsParams = {},
): Promise<PaginatedData<WalletTransaction>> {
  const res = await client.get<LaravelPaginatedResponse<WalletTransaction>>(
    '/wallet/transactions',
    { params },
  )
  return mapPaginated(res.data)
}

export async function getDepositInfo(network: string): Promise<DepositInfo> {
  const res = await client.get<ApiResponse<DepositInfo>>('/wallet/deposit-address', {
    params: { network },
  })
  return res.data.data
}

export async function submitDeposit(payload: SubmitDepositPayload): Promise<Deposit> {
  const res = await client.post<ApiResponse<Deposit>>('/wallet/deposit', payload)
  return res.data.data
}

export async function getWithdrawalFee(
  network: string,
  amount: string,
): Promise<WithdrawalFeeInfo> {
  const res = await client.get<ApiResponse<WithdrawalFeeInfo>>('/wallet/withdrawal-fee', {
    params: { network, amount },
  })
  return res.data.data
}

export async function createWithdrawal(payload: CreateWithdrawalPayload): Promise<Withdrawal> {
  const res = await client.post<ApiResponse<Withdrawal>>('/wallet/withdraw', payload)
  return res.data.data
}
