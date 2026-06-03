interface TabItem {
  key: string
  label: string
  count?: number
}

interface TabsProps {
  tabs: TabItem[]
  active: string
  onChange: (key: string) => void
  className?: string
}

export default function Tabs({ tabs, active, onChange, className = '' }: TabsProps) {
  return (
    <div className={['flex items-center gap-1 border-b border-[#e8e8e8]', className].join(' ')}>
      {tabs.map((tab) => (
        <button
          key={tab.key}
          onClick={() => onChange(tab.key)}
          className={[
            'relative px-4 py-2.5 text-sm font-medium transition-colors whitespace-nowrap',
            active === tab.key
              ? 'text-primary'
              : 'text-[#888888] hover:text-dark',
          ].join(' ')}
        >
          {tab.label}
          {tab.count !== undefined && (
            <span className="mr-1.5 text-xs bg-[#f0f2f5] px-1.5 py-0.5 rounded-full">
              {tab.count}
            </span>
          )}
          {active === tab.key && (
            <span className="absolute bottom-0 right-0 left-0 h-0.5 bg-primary rounded-t" />
          )}
        </button>
      ))}
    </div>
  )
}
