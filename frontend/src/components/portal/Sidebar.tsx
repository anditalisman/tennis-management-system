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
      <div className="flex items-center justify-between border-b border-(--color-ink-900)/10 bg-(--color-paper-raised) px-4 py-3 md:hidden">
        <span className="font-display text-base font-bold text-(--color-court-700)">Zul Tennis Clinic</span>
        <button
          type="button"
          onClick={() => setOpen(true)}
          aria-label="Buka menu navigasi"
          aria-expanded={open}
          className="flex h-9 w-9 items-center justify-center rounded-lg border border-(--color-ink-900)/15 text-(--color-ink-700)"
        >
          <span className="sr-only">Menu</span>
          <span aria-hidden="true">☰</span>
        </button>
      </div>

      {/* Backdrop — closes the drawer, mobile only, sits under the drawer's z-index */}
      {open && (
        <div
          className="fixed inset-0 z-40 bg-(--color-ink-900)/35 md:hidden"
          onClick={() => setOpen(false)}
          aria-hidden="true"
        />
      )}

      {/*
        Fixed + off-canvas (translate-x-full) on mobile so it overlays the
        page from the left instead of being a flex sibling that gets pushed
        around by the rest of the layout. md:static returns it to the normal
        desktop row layout, always visible, no transform.
      */}
      <aside
        className={cn(
          "fixed inset-y-0 left-0 z-50 w-64 shrink-0 overflow-y-auto border-r border-(--color-ink-900)/10 bg-(--color-paper-raised) px-4 py-6 transition-transform duration-200 ease-out",
          "md:static md:z-auto md:translate-x-0",
          open ? "translate-x-0" : "-translate-x-full",
        )}
      >
        <div className="mb-6 flex items-center justify-between px-2">
          <div>
            <span className="font-display text-lg font-bold text-(--color-court-700)">Zul Tennis Clinic</span>
            <p className="text-xs text-(--color-ink-500)">Portal</p>
          </div>
          <button
            type="button"
            onClick={() => setOpen(false)}
            aria-label="Tutup menu navigasi"
            className="rounded-lg p-1 text-(--color-ink-500) hover:bg-(--color-ink-900)/6 md:hidden"
          >
            <span aria-hidden="true">✕</span>
          </button>
        </div>
        <NavLinks sections={sections} onNavigate={() => setOpen(false)} />
      </aside>
    </>
  );
}
