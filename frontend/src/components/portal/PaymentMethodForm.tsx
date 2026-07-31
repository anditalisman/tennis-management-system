"use client";

import { useActionState } from "react";
import { createPaymentMethodAction, updatePaymentMethodAction } from "@/lib/actions/payment-methods";
import { Input, Select, Textarea } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

type PaymentMethod = {
  id: number;
  type: string;
  label: string;
  details: string | null;
  image_url: string | null;
  is_active: boolean;
};

export function PaymentMethodForm({ method }: { method?: PaymentMethod }) {
  const action = method ? updatePaymentMethodAction.bind(null, method.id) : createPaymentMethodAction;
  const [state, formAction, pending] = useActionState(action, undefined);
  const fieldErrors = state?.fieldErrors ?? {};

  return (
    <form action={formAction} className="flex flex-col gap-4">
      {state?.error && <Alert tone="crit">{state.error}</Alert>}
      <Select label="Jenis" name="type" required defaultValue={method?.type ?? "qris"} error={fieldErrors.type}>
        <option value="qris">QRIS</option>
        <option value="bank_transfer">Transfer Bank</option>
        <option value="cash">Tunai</option>
        <option value="other">Lainnya</option>
      </Select>
      <Input
        label="Nama metode"
        name="label"
        required
        defaultValue={method?.label}
        error={fieldErrors.label}
        placeholder="mis. QRIS Zul Tennis Clinic, BCA — Zul Tennis Clinic"
      />
      <Textarea
        label="Detail (opsional)"
        name="details"
        defaultValue={method?.details ?? ""}
        placeholder="mis. a.n. Zul Tennis Clinic, No. Rek 1234567890"
        rows={3}
      />
      <div className="flex flex-col gap-1.5">
        <label className="text-sm font-semibold text-(--color-ink-900)" htmlFor="image">
          Gambar QRIS / logo bank (opsional)
        </label>
        {method?.image_url && (
          // eslint-disable-next-line @next/next/no-img-element
          <img src={method.image_url} alt={method.label} className="h-32 w-32 rounded-lg border border-(--color-ink-900)/10 object-contain" />
        )}
        <input
          id="image"
          name="image"
          type="file"
          accept="image/*"
          className="rounded-lg border border-(--color-ink-900)/15 bg-(--color-paper-raised) px-3.5 py-2.5 text-sm text-(--color-ink-900) file:mr-3 file:rounded-full file:border-0 file:bg-(--color-court-600) file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white"
        />
        <p className="text-xs text-(--color-ink-500)">Maks. 10MB. {method?.image_url ? "Kosongkan untuk mempertahankan gambar saat ini." : ""}</p>
      </div>
      <Select label="Status" name="is_active" defaultValue={method ? (method.is_active ? "1" : "0") : "1"}>
        <option value="1">Aktif</option>
        <option value="0">Nonaktif</option>
      </Select>
      <Button type="submit" loading={pending} className="self-end">
        {method ? "Simpan Perubahan" : "Tambah Metode"}
      </Button>
    </form>
  );
}
