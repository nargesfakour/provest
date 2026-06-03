import { ReactNode } from 'react'

interface CardProps {
  children: ReactNode
  className?: string
  padding?: boolean
}

export default function Card({ children, className = '', padding = true }: CardProps) {
  return (
    <div
      className={[
        'bg-white border border-[#e8e8e8] rounded-xl',
        padding ? 'p-4' : '',
        className,
      ]
        .filter(Boolean)
        .join(' ')}
    >
      {children}
    </div>
  )
}

interface CardHeaderProps {
  title: string
  action?: ReactNode
}

export function CardHeader({ title, action }: CardHeaderProps) {
  return (
    <div className="flex items-center justify-between mb-4">
      <h3 className="font-semibold text-dark">{title}</h3>
      {action && <div>{action}</div>}
    </div>
  )
}
