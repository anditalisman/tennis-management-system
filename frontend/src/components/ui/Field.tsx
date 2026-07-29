import { forwardRef } from "react";
import type { InputHTMLAttributes, ReactNode, SelectHTMLAttributes, TextareaHTMLAttributes } from "react";
import { cn } from "./cn";

const FIELD_BASE =
  "w-full rounded-lg border border-(--color-ink-900)/15 bg-(--color-paper-raised) px-3.5 py-2.5 text-sm text-(--color-ink-900) placeholder:text-(--color-ink-300) focus:border-(--color-court-500) focus:outline-none focus:ring-2 focus:ring-(--color-court-500)/20 disabled:opacity-50";

function FieldWrapper({
  label,
  htmlFor,
  error,
  hint,
  required,
  children,
}: {
  label?: string;
  htmlFor?: string;
  error?: string;
  hint?: string;
  required?: boolean;
  children: ReactNode;
}) {
  return (
    <div className="flex flex-col gap-1.5">
      {label && (
        <label htmlFor={htmlFor} className="text-sm font-semibold text-(--color-ink-900)">
          {label}
          {required && <span className="text-(--color-crit)"> *</span>}
        </label>
      )}
      {children}
      {hint && !error && <p className="text-xs text-(--color-ink-500)">{hint}</p>}
      {error && <p className="text-xs font-medium text-(--color-crit)">{error}</p>}
    </div>
  );
}

export type InputProps = InputHTMLAttributes<HTMLInputElement> & {
  label?: string;
  error?: string;
  hint?: string;
};

export const Input = forwardRef<HTMLInputElement, InputProps>(
  ({ className, label, error, hint, id, required, ...props }, ref) => (
    <FieldWrapper label={label} htmlFor={id} error={error} hint={hint} required={required}>
      <input
        ref={ref}
        id={id}
        required={required}
        className={cn(FIELD_BASE, error && "border-(--color-crit) focus:border-(--color-crit) focus:ring-(--color-crit)/20", className)}
        {...props}
      />
    </FieldWrapper>
  ),
);
Input.displayName = "Input";

export type TextareaProps = TextareaHTMLAttributes<HTMLTextAreaElement> & {
  label?: string;
  error?: string;
  hint?: string;
};

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(
  ({ className, label, error, hint, id, required, rows = 4, ...props }, ref) => (
    <FieldWrapper label={label} htmlFor={id} error={error} hint={hint} required={required}>
      <textarea
        ref={ref}
        id={id}
        required={required}
        rows={rows}
        className={cn(FIELD_BASE, "resize-y", error && "border-(--color-crit) focus:border-(--color-crit) focus:ring-(--color-crit)/20", className)}
        {...props}
      />
    </FieldWrapper>
  ),
);
Textarea.displayName = "Textarea";

export type SelectProps = SelectHTMLAttributes<HTMLSelectElement> & {
  label?: string;
  error?: string;
  hint?: string;
};

export const Select = forwardRef<HTMLSelectElement, SelectProps>(
  ({ className, label, error, hint, id, required, children, ...props }, ref) => (
    <FieldWrapper label={label} htmlFor={id} error={error} hint={hint} required={required}>
      <select
        ref={ref}
        id={id}
        required={required}
        className={cn(FIELD_BASE, "appearance-none bg-[url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 20 20%22 fill=%22%235b6b60%22><path d=%22M5.25 7.5l4.75 5 4.75-5H5.25z%22/></svg>')] bg-[length:1.1rem] bg-[right_0.6rem_center] bg-no-repeat pr-9", error && "border-(--color-crit)", className)}
        {...props}
      >
        {children}
      </select>
    </FieldWrapper>
  ),
);
Select.displayName = "Select";
