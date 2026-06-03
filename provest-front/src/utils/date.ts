// Jalali (Shamsi) calendar conversion — no external dependency
function toJalali(gy: number, gm: number, gd: number): [number, number, number] {
  const g_d_no = [
    365 * gy + Math.floor((gy + 3) / 4) - Math.floor((gy + 99) / 100) +
    Math.floor((gy + 399) / 400),
    0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334,
  ]
  const gDayNo = g_d_no[0] + g_d_no[gm] + gd - 1
  if (gm > 2) {
    const leap = (gy % 4 === 0 && gy % 100 !== 0) || gy % 400 === 0
    if (leap) g_d_no[0]++
  }
  let jy = 979
  let jDayNo = gDayNo - (365 * 1600 + Math.floor(1600 / 4) - Math.floor(1600 / 100) + Math.floor(1600 / 400))
  let j = Math.floor(jDayNo / 365.25)
  jDayNo -= 365 * j + Math.floor(j / 4) - Math.floor(j / 100) + Math.floor(j / 400)
  if (jDayNo < 0) {
    j--
    jDayNo += 365 + (j % 4 === 0 && (j % 100 !== 0 || j % 400 === 0) ? 1 : 0)
  }
  jy += j

  const j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29]
  let jm = 0
  for (let i = 0; i < 12; i++) {
    const dm = j_days_in_month[i]
    if (jDayNo < dm) { jm = i; break }
    jDayNo -= dm
  }
  const jd = jDayNo + 1

  return [jy, jm + 1, jd]
}

function pad(n: number): string {
  return n < 10 ? `0${n}` : String(n)
}

export function formatDate(isoString: string): string {
  const d = new Date(isoString)
  const [jy, jm, jd] = toJalali(d.getFullYear(), d.getMonth() + 1, d.getDate())
  return `${jy}/${pad(jm)}/${pad(jd)}`
}

export function formatDateTime(isoString: string): string {
  const d = new Date(isoString)
  const [jy, jm, jd] = toJalali(d.getFullYear(), d.getMonth() + 1, d.getDate())
  const h = pad(d.getHours())
  const min = pad(d.getMinutes())
  return `${jy}/${pad(jm)}/${pad(jd)} ${h}:${min}`
}

export function timeAgo(isoString: string): string {
  const diff = Math.floor((Date.now() - new Date(isoString).getTime()) / 1000)

  if (diff < 60) return `${diff} ثانیه پیش`
  if (diff < 3600) return `${Math.floor(diff / 60)} دقیقه پیش`
  if (diff < 86400) return `${Math.floor(diff / 3600)} ساعت پیش`
  if (diff < 2592000) return `${Math.floor(diff / 86400)} روز پیش`
  return formatDate(isoString)
}
