"use client";

import { useActionState } from "react";
import { createPackageAction, updatePackageAction } from "@/lib/actions/packages";
import { Input, Select } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

type Package = {
  id: number;
  name: string;
  session_count: number;
  validity_days: number;
  price: number;
  type: string;
  status?: string;
};

export function PackageForm({ pkg }: { pkg?: Package }) {
  const action = pkg ? updatePackageAction.bind(null, pkg.id) : createPackageAction;
  const [state, formAction, pending] = useActionState(action, undefined);
  const fieldErrors = state?.fieldErrors ?? {};

  return (
    <form action={formAction} className="flex flex-col gap-4">
      {state?.error && <Alert tone="crit">{state.error}</Alert>}
      <Input label="Nama paket" name="name" required defaultValue={pkg?.name} error={fieldErrors.name} />
      <Select label="Jenis paket" name="type" required defaultValue={pkg?.type ?? "kelompok"} error={fieldErrors.type}>
        <option value="private">Private</option>
        <option value="kelompok">Kelompok (jam digabung dengan peserta lain)</option>
        <option value="korporat">Korporat (instansi/perusahaan)</option>
      </Select>
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
