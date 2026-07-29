"use client";

import { useState } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import type { NavSection } from "@/lib/nav";
import { cn } from "@/components/ui/cn";

function NavLinks({ sections, onNavigate }: { sections: NavSection[]; onNavigate?: () => void }) {
  const pathname = usePathname();

  return (
    <nav className="flex flex-col gap-6">
      {sections.map((section) => (
        <div key={section.title}>
          <p className="px-3 text-xs font-semibold uppercase tracking-wide text-(--color-ink-300)">{section.title}</p>
          <div className="mt-1.5 flex flex-col gap-0.5">
            {section.items.map((item) => {
              const active = pathname === item.href || pathname.startsWith(`${item.href}/`);
              return (
                <Link
                  key={item.href}
                  href={item.href}
                  onClick={onNavigate}
                  className={cn(
                    "rounded-lg px-3 py-2 text-sm font-medium transition-colors",
                    active
                      ? "bg-(--color-court-600) text-white"
                      : "text-(--color-ink-700) hover:bg-(--color-ink-900)/6",
                  )}
                >
                  {item.label}
                </Link>
              );
            })}
          </div>
        </div>
      ))}
    </nav>
  );
}

export function Sidebar({ sections }: { sections: NavSection[] }) {
  const [open, setOpen] = useState(false);

  return (
    <>
      <div className="flex items-center justify-between border-b border-(--color-ink-900)/10 px-4 py-3 md:hidden">
        <span className="font-display text-base font-bold text-(--color-court-700)">Zul Tennis Clinic</span>
        <button
          type="button"
          onClick={() => setOpen((v) => !v)}
          className="rounded-lg border border-(--color-ink-900)/15 px-3 py-1.5 text-sm font-medium"
          aria-expanded={open}
        >
          Menu
        </button>
      </div>

      <aside
        className={cn(
          "w-64 shrink-0 border-r border-(--color-ink-900)/10 bg-(--color-paper-raised) px-4 py-6 md:block",
          open ? "block" : "hidden",
        )}
      >
        <div className="mb-6 hidden px-2 md:block">
          <span className="font-display text-lg font-bold text-(--color-court-700)">Zul Tennis Clinic</span>
          <p className="text-xs text-(--color-ink-500)">Portal</p>
        </div>
        <NavLinks sections={sections} onNavigate={() => setOpen(false)} />
      </aside>
    </>
  );
}
