import Link from "next/link";
import { cn } from "./cn";

export function Pagination({
  currentPage,
  lastPage,
  makeHref,
}: {
  currentPage: number;
  lastPage: number;
  makeHref: (page: number) => string;
}) {
  if (lastPage <= 1) return null;

  const pages = Array.from({ length: lastPage }, (_, i) => i + 1).filter(
    (p) => p === 1 || p === lastPage || Math.abs(p - currentPage) <= 1,
  );

  return (
    <nav className="flex items-center justify-center gap-1 pt-4" aria-label="Pagination">
      {pages.map((page, idx) => {
        const prev = pages[idx - 1];
        const showEllipsis = prev !== undefined && page - prev > 1;
        return (
          <span key={page} className="flex items-center gap-1">
            {showEllipsis && <span className="px-1 text-(--color-ink-300)">…</span>}
            <Link
              href={makeHref(page)}
              className={cn(
                "flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium",
                page === currentPage
                  ? "bg-(--color-court-600) text-white"
                  : "text-(--color-ink-700) hover:bg-(--color-ink-900)/8",
              )}
            >
              {page}
            </Link>
          </span>
        );
      })}
    </nav>
  );
}
