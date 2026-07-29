"use client";

import { useActionState } from "react";
import { submitPaymentAction } from "@/lib/actions/invoices";
import { Input, Select } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

export function PaymentForm({ invoiceId, defaultAmount }: { invoiceId: number; defaultAmount: number }) {
  const action = submitPaymentAction.bind(null, invoiceId);
  const [state, formAction, pending] = useActionState(action, undefined);
  const fieldErrors = state?.fieldErrors ?? {};

  return (
    <form action={formAction} className="flex flex-col gap-4">
      {state?.error && <Alert tone="crit">{state.error}</Alert>}
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
          className="rounded-lg border border-(--color-ink-900)/15 bg-(--color-paper-raised) px-3.5 py-2.5 text-sm text-(--color-ink-900) file:mr-3 file:rounded-full file:border-0 file:bg-(--color-court-600) file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white"
        />
      </div>
      <Button type="submit" loading={pending} className="self-end">
        Kirim Pembayaran
      </Button>
    </form>
  );
}
