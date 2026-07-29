import type { ReactNode } from "react";
import { cn } from "./cn";

export function Alert({ tone = "info", children }: { tone?: "info" | "warn" | "crit" | "good"; children: ReactNode }) {
  const toneClasses: Record<string, string> = {
    info: "border-(--color-info)/25 bg-(--color-info)/8 text-(--color-info)",
    warn: "border-(--color-warn)/25 bg-(--color-warn)/8 text-(--color-warn)",
    crit: "border-(--color-crit)/25 bg-(--color-crit)/8 text-(--color-crit)",
    good: "border-(--color-good)/25 bg-(--color-good)/8 text-(--color-good)",
  };
  return <div className={cn("rounded-xl border px-4 py-3 text-sm font-medium", toneClasses[tone])}>{children}</div>;
}

export function EmptyState({ title, description, action }: { title: string; description?: string; action?: ReactNode }) {
  return (
    <div className="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-(--color-ink-900)/15 px-6 py-14 text-center">
      <p className="font-display text-base font-bold text-(--color-ink-900)">{title}</p>
      {description && <p className="max-w-sm text-sm text-(--color-ink-500)">{description}</p>}
      {action}
    </div>
  );
}

export function Spinner({ className }: { className?: string }) {
  return (
    <span
      className={cn("inline-block h-5 w-5 animate-spin rounded-full border-2 border-current border-t-transparent", className)}
      aria-label="Memuat"
    />
  );
}

export function StatTile({ label, value, hint }: { label: string; value: ReactNode; hint?: string }) {
  return (
    <div className="rounded-2xl border border-(--color-ink-900)/10 bg-(--color-paper-raised) px-5 py-4">
      <p className="text-xs font-semibold uppercase tracking-wide text-(--color-ink-500)">{label}</p>
      <p className="mt-1.5 font-display text-2xl font-bold text-(--color-ink-900)">{value}</p>
      {hint && <p className="mt-1 text-xs text-(--color-ink-500)">{hint}</p>}
    </div>
  );
}
