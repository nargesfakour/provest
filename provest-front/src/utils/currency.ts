export function formatUsdt(value: string): string {
  const parts = value.split('.')
  const intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',')
  const decPart = parts[1] ? parts[1].slice(0, 2).padEnd(2, '0') : '00'
  return `${intPart}.${decPart} USDT`
}

export function formatPrice(value: string): string {
  return value
}

export function formatPercent(value: string): string {
  const num = parseFloat(value)
  return `${(num * 100).toFixed(2)}%`
}

export function formatPnl(value: string): { text: string; positive: boolean } {
  const num = parseFloat(value)
  const sign = num >= 0 ? '+' : ''
  const parts = Math.abs(num).toFixed(2).split('.')
  const intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',')
  return {
    text: `${sign}${num < 0 ? '-' : ''}${intPart}.${parts[1]} USDT`,
    positive: num >= 0,
  }
}

export function formatNumber(value: string | number): string {
  const str = String(value)
  const parts = str.split('.')
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',')
  return parts.join('.')
}
