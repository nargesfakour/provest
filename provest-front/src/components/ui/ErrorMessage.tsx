import { AlertCircle, RefreshCw } from 'lucide-react'

interface ErrorMessageProps {
  message?: string
  onRetry?: () => void
}

export default function ErrorMessage({
  message = 'خطایی رخ داد',
  onRetry,
}: ErrorMessageProps) {
  return (
    <div className="flex flex-col items-center justify-center gap-3 py-12 text-center">
      <div className="w-12 h-12 rounded-full bg-no-bg flex items-center justify-center">
        <AlertCircle size={22} className="text-no" />
      </div>
      <p className="text-sm text-[#888888]">{message}</p>
      {onRetry && (
        <button
          onClick={onRetry}
          className="inline-flex items-center gap-1.5 text-sm text-primary hover:underline"
        >
          <RefreshCw size={14} />
          <span>تلاش مجدد</span>
        </button>
      )}
    </div>
  )
}
