"use client";

import { useActionState, useState } from "react";
import { submitPaymentAction } from "@/lib/actions/invoices";
import { Input, Select } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

const MAX_FILE_SIZE = 10 * 1024 * 1024;

// PaymentMethod.type is more granular than Payment.method (which only
// tracks transfer/cash/qris/gateway) — several configured methods can map
// to the same underlying value, e.g. two different bank accounts both
// submit as "transfer". reference_no is where the user notes specifics.
const TYPE_TO_METHOD: Record<string, string> = {
  qris: "qris",
  bank_transfer: "transfer",
  cash: "cash",
  other: "transfer",
};

type PaymentMethod = { id: number; type: string; label: string; details: string | null; image_url: string | null };

export function PaymentForm({
  invoiceId,
  defaultAmount,
  methods,
}: {
  invoiceId: number;
  defaultAmount: number;
  methods: PaymentMethod[];
}) {
  const action = submitPaymentAction.bind(null, invoiceId);
  const [state, formAction, pending] = useActionState(action, undefined);
  const fieldErrors = state?.fieldErrors ?? {};
  const [sizeError, setSizeError] = useState<string | null>(null);

  function handleProofChange(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0];
    if (file && file.size > MAX_FILE_SIZE) {
      setSizeError(`"${file.name}" melebihi 10MB. Pilih file yang lebih kecil.`);
      e.target.value = "";
    } else {
      setSizeError(null);
    }
  }

  const options =
    methods.length > 0
      ? methods.map((m) => ({ value: TYPE_TO_METHOD[m.type] ?? "transfer", label: m.label }))
      : [
          { value: "transfer", label: "Transfer Bank" },
          { value: "cash", label: "Tunai" },
          { value: "qris", label: "QRIS" },
        ];

  return (
    <div className="flex flex-col gap-5">
      {methods.length > 0 && (
        <div className="flex flex-col gap-3">
          <p className="text-sm font-semibold text-(--color-ink-900)">Bayar ke salah satu metode berikut:</p>
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            {methods.map((m) => (
              <div key={m.id} className="flex items-center gap-3 rounded-lg border border-(--color-ink-900)/10 p-3">
                {m.image_url ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img src={m.image_url} alt={m.label} className="h-16 w-16 shrink-0 rounded-lg object-contain" />
                ) : (
                  <div className="h-16 w-16 shrink-0 rounded-lg bg-(--color-ink-900)/5" />
                )}
                <div className="min-w-0">
                  <p className="truncate text-sm font-semibold text-(--color-ink-900)">{m.label}</p>
                  {m.details && <p className="text-xs text-(--color-ink-500)">{m.details}</p>}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      <form action={formAction} className="flex flex-col gap-4">
        {(sizeError ?? state?.error) && <Alert tone="crit">{sizeError ?? state?.error}</Alert>}
        <Select label="Metode pembayaran" name="method" required defaultValue={options[0]?.value}>
          {options.map((o, i) => (
            <option key={`${o.value}-${i}`} value={o.value}>
              {o.label}
            </option>
          ))}
        </Select>
        <Input label="Jumlah (Rp)" name="amount" type="number" min={0} required defaultValue={defaultAmount} error={fieldErrors.amount} />
        <Input label="No. Referensi (opsional)" name="reference_no" />
        <div className="flex flex-col gap-1.5">
          <label className="text-sm font-semibold text-(--color-ink-900)" htmlFor="proof">
            Bukti transfer (opsional)
          </label>
          <input
            id="proof"
            name="proof"
            type="file"
            accept="image/*,application/pdf"
            onChange={handleProofChange}
            className="rounded-lg border border-(--color-ink-900)/15 bg-(--color-paper-raised) px-3.5 py-2.5 text-sm text-(--color-ink-900) file:mr-3 file:rounded-full file:border-0 file:bg-(--color-court-600) file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white"
          />
          <p className="text-xs text-(--color-ink-500)">Maks. 10MB.</p>
        </div>
        <Button type="submit" loading={pending} className="self-end">
          Kirim Pembayaran
        </Button>
      </form>
    </div>
  );
}
