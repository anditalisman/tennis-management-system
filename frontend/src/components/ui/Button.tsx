import { forwardRef } from "react";
import type { ButtonHTMLAttributes } from "react";
import { cn } from "./cn";

type Variant = "primary" | "secondary" | "outline" | "ghost" | "danger";
type Size = "sm" | "md" | "lg";

const VARIANT_CLASSES: Record<Variant, string> = {
  primary: "bg-(--color-court-600) text-white hover:bg-(--color-court-700) disabled:bg-(--color-court-600)/50",
  secondary: "bg-(--color-ball-500) text-(--color-court-900) hover:bg-(--color-ball-400) disabled:opacity-50",
  outline: "border border-(--color-ink-900)/20 text-(--color-ink-900) hover:bg-(--color-ink-900)/5 disabled:opacity-50",
  ghost: "text-(--color-ink-700) hover:bg-(--color-ink-900)/5 disabled:opacity-50",
  danger: "bg-(--color-crit) text-white hover:bg-(--color-crit)/90 disabled:opacity-50",
};

const SIZE_CLASSES: Record<Size, string> = {
  sm: "px-3 py-1.5 text-xs",
  md: "px-4 py-2.5 text-sm",
  lg: "px-6 py-3 text-sm",
};

export type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: Variant;
  size?: Size;
  loading?: boolean;
};

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant = "primary", size = "md", loading, disabled, children, ...props }, ref) => {
    return (
      <button
        ref={ref}
        disabled={disabled || loading}
        className={cn(
          "inline-flex items-center justify-center gap-2 rounded-full font-semibold transition-colors disabled:cursor-not-allowed",
          VARIANT_CLASSES[variant],
          SIZE_CLASSES[size],
          className,
        )}
        {...props}
      >
        {loading && (
          <span className="h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-t-transparent" aria-hidden />
        )}
        {children}
      </button>
    );
  },
);
Button.displayName = "Button";
