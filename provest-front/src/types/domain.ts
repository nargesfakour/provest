export type UserStatus = 'active' | 'suspended' | 'banned' | 'pending_verification'

export interface User {
  ulid: string
  name: string
  email: string
  balance: string
  status: UserStatus
  created_at: string
}

export interface Admin {
  ulid: string
  name: string
  email: string
  roles: string[]
  permissions: string[]
  created_at: string
}

export interface Category {
  ulid: string
  name: string
  slug: string
  is_active: boolean
  sub_categories?: SubCategory[]
}

export interface SubCategory {
  ulid: string
  category_ulid: string
  name: string
  slug: string
  is_active: boolean
}

export type EventStatus = 'Pending' | 'Open' | 'Closed' | 'Settled'
export type EventOutcome = 'Yes' | 'No' | null

export interface EventSubCategory {
  ulid: string
  name: string
  category: {
    ulid: string
    name: string
  }
}

export interface Event {
  ulid: string
  title: string
  description: string | null
  sub_category: EventSubCategory
  status: EventStatus
  outcome: EventOutcome
  yes_initial_price: string
  no_initial_price: string
  fee_rate: string
  starts_at: string | null
  ends_at: string | null
  resolve_at: string | null
  created_at: string
}

export interface PriceLevel {
  price: string
  depth: string
}

export interface OrderBook {
  yes: PriceLevel[]
  no: PriceLevel[]
}

export type OrderStatus = 'open' | 'partial' | 'filled' | 'cancelled'
export type Side = 'yes' | 'no'

export interface Order {
  ulid: string
  event_ulid: string
  event_title: string
  side: Side
  price: string
  quantity: string
  filled_quantity: string
  remaining: string
  status: OrderStatus
  created_at: string
}

export interface Position {
  event_ulid: string
  event_title: string
  side: Side
  quantity: string
  avg_price: string
  current_price: string
  pnl: string
  updated_at: string
}

export interface Trade {
  ulid: string
  price: string
  quantity: string
  side: Side
  created_at: string
}

export type WalletTxType = 'deposit' | 'withdrawal' | 'lock' | 'unlock' | 'win' | 'loss' | 'fee'

export interface WalletTransaction {
  ulid: string
  amount: string
  type: WalletTxType
  balance_after: string
  reference_type: string | null
  reference_id: number | null
  created_at: string
}

export type DepositStatus = 'pending' | 'confirmed' | 'rejected'

export interface Deposit {
  ulid: string
  tx_id: string
  amount: string
  coin: string
  network: string
  status: DepositStatus
  confirmed_at: string | null
  created_at: string
}

export type WithdrawalStatus = 'pending' | 'processing' | 'done' | 'failed'

export interface Withdrawal {
  ulid: string
  amount: string
  fee: string
  network: string
  destination_address: string
  status: WithdrawalStatus
  created_at: string
}

// Admin-extended types (include user info not present in user-facing types)
export interface AdminDeposit extends Deposit {
  user_ulid: string
  user_name: string
  user_email: string
}

export interface AdminWithdrawal extends Withdrawal {
  user_ulid: string
  user_name: string
  user_email: string
}

export interface AdminOrder extends Order {
  user_ulid: string
  user_name: string
}

export interface AdminPosition extends Omit<Position, 'event_title'> {
  user_ulid: string
  user_name: string
}

export interface AdminTrade extends Trade {
  buyer_ulid?: string
  seller_ulid?: string
}

export interface AdminStats {
  total_users: number
  active_events: number
  total_volume: string
  today_fees: string
}

export interface FeeReport {
  event_ulid: string
  event_title: string
  trade_count: number
  total_fee: string
}

export interface UserBalance {
  user_ulid: string
  user_name: string
  user_email: string
  balance: string
}
