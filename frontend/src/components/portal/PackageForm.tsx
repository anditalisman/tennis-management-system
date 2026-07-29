"use client";

import { useActionState } from "react";
import { createPackageAction, updatePackageAction } from "@/lib/actions/packages";
import { Input, Select } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

type Option = { id: number; name: string };
type Package = {
  id: number;
  program_id: number;
  name: string;
  session_count: number;
  validity_days: number;
  price: number;
  type: string;
  status?: string;
};

export function PackageForm({ pkg, programs }: { pkg?: Package; programs: Option[] }) {
  const action = pkg ? updatePackageAction.bind(null, pkg.id) : createPackageAction;
  const [state, formAction, pending] = useActionState(action, undefined);
  const fieldErrors = state?.fieldErrors ?? {};

  return (
    <form action={formAction} className="flex flex-col gap-4">
      {state?.error && <Alert tone="crit">{state.error}</Alert>}
      <Select label="Program" name="program_id" required defaultValue={pkg?.program_id ?? ""} error={fieldErrors.program_id}>
        <option value="" disabled>
          Pilih program
        </option>
        {programs.map((p) => (
          <option key={p.id} value={p.id}>
            {p.name}
          </option>
        ))}
      </Select>
      <Input label="Nama paket" name="name" required defaultValue={pkg?.name} error={fieldErrors.name} />
      <div className="grid grid-cols-2 gap-4">
        <Input
          label="Jumlah sesi"
          name="session_count"
          type="number"
          min={1}
          required
          defaultValue={pkg?.session_count}
          error={fieldErrors.session_count}
        />
        <Input label="Masa berlaku (hari)" name="validity_days" type="number" min={1} defaultValue={pkg?.validity_days ?? 90} />
      </div>
      <Input label="Harga (Rp)" name="price" type="number" min={0} required defaultValue={pkg?.price} error={fieldErrors.price} />
      {pkg && (
        <Select label="Status" name="status" defaultValue={pkg.status ?? "active"}>
          <option value="active">Aktif</option>
          <option value="inactive">Nonaktif</option>
        </Select>
      )}
      <Button type="submit" loading={pending} className="self-end">
        {pkg ? "Simpan Perubahan" : "Buat Paket"}
      </Button>
    </form>
  );
}
