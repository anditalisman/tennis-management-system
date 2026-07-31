"use client";

import { useActionState } from "react";
import { checkoutPackageAction } from "@/lib/actions/packages";
import { Input, Select } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

type Option = { id: string; full_name: string };

export function PackageCheckoutForm({ packageId, guardianChildren }: { packageId: number; guardianChildren?: Option[] }) {
  const action = checkoutPackageAction.bind(null, packageId);
  const [state, formAction, pending] = useActionState(action, undefined);
  const fieldErrors = state?.fieldErrors ?? {};

  return (
    <form action={formAction} className="flex flex-col gap-4">
      {state?.error && <Alert tone="crit">{state.error}</Alert>}
      {guardianChildren && (
        <Select label="Untuk anak" name="participant_id" required defaultValue="" error={fieldErrors.participant_id}>
          <option value="" disabled>
            Pilih anak
          </option>
          {guardianChildren.map((c) => (
            <option key={c.id} value={c.id}>
              {c.full_name}
            </option>
          ))}
        </Select>
      )}
      <Input label="Kode voucher (opsional)" name="voucher_code" error={fieldErrors.voucher_code} />
      <Button type="submit" loading={pending} className="self-end">
        Daftar &amp; Lanjut Bayar
      </Button>
    </form>
  );
}
