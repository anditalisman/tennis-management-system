export function formatCurrency(amount: number): string {
  return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(amount);
}

export function formatDate(value: string | null | undefined, options: Intl.DateTimeFormatOptions = {}): string {
  if (!value) return "-";
  return new Intl.DateTimeFormat("id-ID", { day: "numeric", month: "short", year: "numeric", ...options }).format(new Date(value));
}

export function formatDateTime(value: string | null | undefined): string {
  if (!value) return "-";
  return new Intl.DateTimeFormat("id-ID", {
    day: "numeric",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(new Date(value));
}

export function formatTime(value: string | null | undefined): string {
  if (!value) return "-";
  // Handles both "HH:mm" strings and ISO datetimes.
  if (/^\d{2}:\d{2}/.test(value)) return value.slice(0, 5);
  return new Intl.DateTimeFormat("id-ID", { hour: "2-digit", minute: "2-digit" }).format(new Date(value));
}
