"use client";

import { useActionState, useState } from "react";
import { createInvoiceAction } from "@/lib/actions/invoices";
import { Input, Select } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

type ParticipantOption = { id: string; full_name: string; registration_no: string };
type PackageOption = { id: number; name: string; price: number };

export function InvoiceForm({ participants, packages }: { participants: ParticipantOption[]; packages: PackageOption[] }) {
  const [state, formAction, pending] = useActionState(createInvoiceAction, undefined);
  const [itemType, setItemType] = useState("package");
  const fieldErrors = state?.fieldErrors ?? {};

  return (
    <form action={formAction} className="flex flex-col gap-4">
      {state?.error && <Alert tone="crit">{state.error}</Alert>}
      <Select label="Peserta" name="participant_id" required defaultValue="" error={fieldErrors.participant_id}>
        <option value="" disabled>
          Pilih peserta
        </option>
        {participants.map((p) => (
          <option key={p.id} value={p.id}>
            {p.full_name} ({p.registration_no})
          </option>
        ))}
      </Select>
      <Input label="Jatuh tempo" name="due_date" type="date" required error={fieldErrors.due_date} />

      <Select label="Jenis item" value={itemType} onChange={(e) => setItemType(e.target.value)}>
        <option value="package">Paket latihan</option>
        <option value="other">Lainnya</option>
      </Select>

      {itemType === "package" ? (
        <Select label="Paket" name="package_id" required defaultValue="">
          <option value="" disabled>
            Pilih paket
          </option>
          {packages.map((p) => (
            <option key={p.id} value={p.id}>
              {p.name}
            </option>
          ))}
        </Select>
      ) : (
        <>
          <Input label="Deskripsi" name="description" required />
          <Input label="Harga satuan (Rp)" name="unit_price" type="number" min={0} required />
        </>
      )}

      <Input label="Diskon (Rp, opsional)" name="discount_amount" type="number" min={0} />
      <Input label="Kode voucher (opsional)" name="voucher_code" />

      <Button type="submit" loading={pending} className="self-end">
        Buat Tagihan
      </Button>
    </form>
  );
}
