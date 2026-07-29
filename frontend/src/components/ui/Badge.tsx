import type { ReactNode } from "react";
import { cn } from "./cn";

type Tone = "neutral" | "good" | "warn" | "crit" | "info" | "court";

const TONE_CLASSES: Record<Tone, string> = {
  neutral: "bg-(--color-ink-900)/8 text-(--color-ink-700)",
  good: "bg-(--color-good)/12 text-(--color-good)",
  warn: "bg-(--color-warn)/14 text-(--color-warn)",
  crit: "bg-(--color-crit)/12 text-(--color-crit)",
  info: "bg-(--color-info)/12 text-(--color-info)",
  court: "bg-(--color-court-500)/12 text-(--color-court-600)",
};

export function Badge({ tone = "neutral", children }: { tone?: Tone; children: ReactNode }) {
  return (
    <span className={cn("inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold", TONE_CLASSES[tone])}>
      {children}
    </span>
  );
}

// Central place to map backend status strings to a tone — keep in sync with
// the enums in each Laravel model when new statuses are added.
const STATUS_TONES: Record<string, Tone> = {
  active: "good",
  approved: "good",
  paid: "good",
  verified: "good",
  present: "good",
  sent: "good",
  published: "good",
  enrolled: "good",
  redeemed: "good",
  rewarded: "good",
  pending: "warn",
  pending_verification: "warn",
  pending_payment: "warn",
  unpaid: "warn",
  partially_paid: "warn",
  waiting: "warn",
  late: "warn",
  draft: "neutral",
  inactive: "neutral",
  suspended: "crit",
  rejected: "crit",
  cancelled: "crit",
  failed: "crit",
  overdue: "crit",
  absent: "crit",
  exhausted: "neutral",
  excused: "info",
  sick: "info",
  left_early: "info",
  graduated: "info",
  scheduled: "info",
  completed: "good",
  converted: "good",
  expired: "crit",
};

export function StatusBadge({ status, label }: { status: string; label?: string }) {
  const tone = STATUS_TONES[status] ?? "neutral";
  return <Badge tone={tone}>{label ?? status.replace(/_/g, " ")}</Badge>;
}
