import type { ReactNode } from "react";

export function PageHeader({
  eyebrow,
  title,
  description,
}: {
  eyebrow: string;
  title: ReactNode;
  description?: ReactNode;
}) {
  return (
    <div className="max-w-2xl">
      <span className="inline-flex items-center rounded-full bg-(--color-court-600)/10 px-3 py-1 text-xs font-bold uppercase tracking-widest text-(--color-court-500)">
        {eyebrow}
      </span>
      <h1 className="mt-4 font-display text-4xl font-extrabold leading-[1.05] text-(--color-ink-900) sm:text-5xl">
        {title}
      </h1>
      {description && <p className="mt-5 text-lg leading-relaxed text-(--color-ink-500)">{description}</p>}
    </div>
  );
}
