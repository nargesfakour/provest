import { forwardRef, InputHTMLAttributes, ReactNode } from 'react'

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  label?: string
  error?: string
  hint?: string
  leftIcon?: ReactNode
  rightIcon?: ReactNode
}

const Input = forwardRef<HTMLInputElement, InputProps>(function Input(
  { label, error, hint, leftIcon, rightIcon, className = '', id, ...rest },
  ref,
) {
  const inputId = id ?? label

  return (
    <div className="flex flex-col gap-1">
      {label && (
        <label htmlFor={inputId} className="text-sm font-medium text-dark">
          {label}
        </label>
      )}
      <div className="relative">
        {rightIcon && (
          <span className="absolute right-3 top-1/2 -translate-y-1/2 text-[#888888]">
            {rightIcon}
          </span>
        )}
        <input
          ref={ref}
          id={inputId}
          className={[
            'w-full rounded-lg border bg-white px-3 py-2 text-sm text-dark placeholder:text-[#bbbbbb]',
            'transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary',
            'disabled:bg-[#f0f2f5] disabled:cursor-not-allowed',
            error ? 'border-no focus:ring-no' : 'border-[#e8e8e8]',
            rightIcon ? 'pr-10' : '',
            leftIcon ? 'pl-10' : '',
            className,
          ]
            .filter(Boolean)
            .join(' ')}
          {...rest}
        />
        {leftIcon && (
          <span className="absolute left-3 top-1/2 -translate-y-1/2 text-[#888888]">
            {leftIcon}
          </span>
        )}
      </div>
      {error && <p className="text-xs text-no">{error}</p>}
      {hint && !error && <p className="text-xs text-[#888888]">{hint}</p>}
    </div>
  )
})

export default Input
