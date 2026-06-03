import { ReactNode } from 'react'

type BadgeVariant =
  | 'yes'
  | 'no'
  | 'default'
  | 'success'
  | 'warning'
  | 'danger'
  | 'info'
  | 'pending'
  | 'open'
  | 'closed'
  | 'settled'

interface BadgeProps {
  variant?: BadgeVariant
  children: ReactNode
  className?: string
}

const variantClasses: Record<BadgeVariant, string> = {
  yes: 'bg-yes-bg text-yes',
  no: 'bg-no-bg text-no',
  default: 'bg-[#f0f2f5] text-[#888888]',
  success: 'bg-yes-bg text-yes',
  warning: 'bg-[#fff3e8] text-[#f07c30]',
  danger: 'bg-no-bg text-no',
  info: 'bg-[#e8f0ff] text-[#3366cc]',
  pending: 'bg-[#fff3e8] text-[#f07c30]',
  open: 'bg-yes-bg text-yes',
  closed: 'bg-[#f0f2f5] text-[#888888]',
  settled: 'bg-[#e8f0ff] text-[#3366cc]',
}

export default function Badge({ variant = 'default', children, className = '' }: BadgeProps) {
  return (
    <span
      className={[
        'inline-flex items-center text-xs font-medium px-2 py-0.5 rounded-full',
        variantClasses[variant],
        className,
      ]
        .filter(Boolean)
        .join(' ')}
    >
      {children}
    </span>
  )
}
