import { logoutAction } from "@/lib/actions/auth";
import { ROLE_LABELS } from "@/lib/roles";
import type { SessionUser } from "@/lib/session";

export function TopBar({ user }: { user: SessionUser }) {
  const roleLabel = user.roles.map((r) => ROLE_LABELS[r] ?? r).join(", ");

  return (
    <header className="flex items-center justify-between border-b border-(--color-ink-900)/10 bg-(--color-paper-raised) px-6 py-3">
      <div className="hidden md:block">
        <p className="text-sm font-semibold text-(--color-ink-900)">{user.name}</p>
        <p className="text-xs text-(--color-ink-500)">{roleLabel}</p>
      </div>
      <form action={logoutAction} className="ml-auto">
        <button
          type="submit"
          className="rounded-full border border-(--color-ink-900)/15 px-4 py-1.5 text-sm font-semibold text-(--color-ink-700) hover:bg-(--color-ink-900)/5"
        >
          Keluar
        </button>
      </form>
    </header>
  );
}
