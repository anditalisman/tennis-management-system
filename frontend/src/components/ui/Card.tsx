import type { HTMLAttributes, ReactNode } from "react";
import { cn } from "./cn";

export function Card({ className, children, ...props }: HTMLAttributes<HTMLDivElement>) {
  return (
    <div
      className={cn("rounded-2xl border border-(--color-ink-900)/10 bg-(--color-paper-raised) shadow-sm", className)}
      {...props}
    >
      {children}
    </div>
  );
}

export function CardHeader({
  title,
  description,
  action,
  className,
}: {
  title: ReactNode;
  description?: ReactNode;
  action?: ReactNode;
  className?: string;
}) {
  return (
    <div className={cn("flex flex-wrap items-start justify-between gap-3 border-b border-(--color-ink-900)/10 px-5 py-4", className)}>
      <div>
        <h2 className="font-display text-lg font-bold text-(--color-ink-900)">{title}</h2>
        {description && <p className="mt-1 text-sm text-(--color-ink-500)">{description}</p>}
      </div>
      {action}
    </div>
  );
}

export function CardBody({ className, children }: { className?: string; children: ReactNode }) {
  return <div className={cn("px-5 py-4", className)}>{children}</div>;
}
