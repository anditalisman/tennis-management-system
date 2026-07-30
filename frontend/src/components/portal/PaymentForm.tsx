"use client";

import { useActionState, useState } from "react";
import { submitPaymentAction } from "@/lib/actions/invoices";
import { Input, Select } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

const MAX_FILE_SIZE = 10 * 1024 * 1024;

export function PaymentForm({ invoiceId, defaultAmount }: { invoiceId: number; defaultAmount: number }) {
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

  return (
    <form action={formAction} className="flex flex-col gap-4">
      {(sizeError ?? state?.error) && <Alert tone="crit">{sizeError ?? state?.error}</Alert>}
      <Select label="Metode pembayaran" name="method" required defaultValue="transfer">
        <option value="transfer">Transfer Bank</option>
        <option value="cash">Tunai</option>
        <option value="qris">QRIS</option>
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
  );
}
