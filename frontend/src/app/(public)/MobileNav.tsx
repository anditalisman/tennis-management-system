"use client";

import Link from "next/link";
import { useState } from "react";

export function MobileNav({ items }: { items: { href: string; label: string }[] }) {
  const [open, setOpen] = useState(false);

  return (
    <div className="lg:hidden">
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        aria-label="Buka menu navigasi"
        aria-expanded={open}
        className="flex h-9 w-9 items-center justify-center rounded-full border border-(--color-ink-900)/15 text-(--color-ink-700)"
      >
        <span className="sr-only">Menu</span>
        {open ? "✕" : "☰"}
      </button>
      {open && (
        <nav className="absolute inset-x-0 top-full border-b border-(--color-ink-900)/10 bg-(--color-paper) px-6 py-4 shadow-lg">
          <ul className="flex flex-col gap-3 text-sm font-medium text-(--color-ink-700)">
            {items.map((item) => (
              <li key={item.href}>
                <Link href={item.href} onClick={() => setOpen(false)} className="hover:text-(--color-court-600)">
                  {item.label}
                </Link>
              </li>
            ))}
          </ul>
        </nav>
      )}
    </div>
  );
}
