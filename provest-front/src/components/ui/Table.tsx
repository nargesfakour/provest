import { ReactNode } from 'react'
import { SkeletonTable } from './Skeleton'

export interface Column<T> {
  header: string
  key?: keyof T
  render?: (row: T, index: number) => ReactNode
  align?: 'right' | 'left' | 'center'
  width?: string
  className?: string
}

interface TableProps<T> {
  columns: Column<T>[]
  data: T[]
  loading?: boolean
  emptyText?: string
  keyExtractor: (row: T, index: number) => string | number
}

const alignClass = {
  right: 'text-right',
  left: 'text-left',
  center: 'text-center',
}

export default function Table<T>({
  columns,
  data,
  loading = false,
  emptyText = 'داده‌ای موجود نیست',
  keyExtractor,
}: TableProps<T>) {
  return (
    <div className="w-full overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-[#e8e8e8]">
            {columns.map((col) => (
              <th
                key={String(col.header)}
                style={col.width ? { width: col.width } : undefined}
                className={[
                  'px-4 py-3 font-medium text-[#888888]',
                  alignClass[col.align ?? 'right'],
                  col.className ?? '',
                ].join(' ')}
              >
                {col.header}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {loading ? (
            <tr>
              <td colSpan={columns.length} className="px-4 py-4">
                <SkeletonTable cols={columns.length} rows={5} />
              </td>
            </tr>
          ) : data.length === 0 ? (
            <tr>
              <td
                colSpan={columns.length}
                className="px-4 py-10 text-center text-[#888888]"
              >
                {emptyText}
              </td>
            </tr>
          ) : (
            data.map((row, index) => (
              <tr
                key={keyExtractor(row, index)}
                className="border-b border-[#e8e8e8] hover:bg-[#f0f2f5] transition-colors"
              >
                {columns.map((col) => (
                  <td
                    key={String(col.header)}
                    className={[
                      'px-4 py-3 text-dark',
                      alignClass[col.align ?? 'right'],
                      col.className ?? '',
                    ].join(' ')}
                  >
                    {col.render
                      ? col.render(row, index)
                      : col.key != null
                        ? String(row[col.key] ?? '')
                        : null}
                  </td>
                ))}
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  )
}
