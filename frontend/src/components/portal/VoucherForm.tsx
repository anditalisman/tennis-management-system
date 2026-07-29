"use client";

import { useActionState } from "react";
import { createVoucherAction } from "@/lib/actions/vouchers";
import { Input, Select } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

export function VoucherForm() {
  const [state, formAction, pending] = useActionState(createVoucherAction, undefined);
  const fieldErrors = state?.fieldErrors ?? {};

  return (
    <form action={formAction} className="flex flex-col gap-4">
      {state?.error && <Alert tone="crit">{state.error}</Alert>}
      <Input
        label="Kode voucher"
        name="code"
        required
        placeholder="HEMAT50"
        error={fieldErrors.code}
        className="uppercase"
      />
      <Select label="Jenis diskon" name="discount_type" required defaultValue="percentage" error={fieldErrors.discount_type}>
        <option value="percentage">Persentase (%)</option>
        <option value="fixed">Nominal (Rp)</option>
      </Select>
      <Input label="Nilai diskon" name="discount_value" type="number" min={0} required error={fieldErrors.discount_value} />
      <Input label="Batas pemakaian (opsional)" name="usage_limit" type="number" min={1} error={fieldErrors.usage_limit} />
      <Input label="Berlaku dari (opsional)" name="valid_from" type="date" error={fieldErrors.valid_from} />
      <Input label="Berlaku sampai (opsional)" name="valid_until" type="date" error={fieldErrors.valid_until} />
      <Button type="submit" loading={pending} className="self-end">
        Buat Voucher
      </Button>
    </form>
  );
}
