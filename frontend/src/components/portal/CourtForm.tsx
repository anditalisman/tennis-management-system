"use client";

import { useActionState } from "react";
import { createCourtAction, updateCourtAction } from "@/lib/actions/courts";
import { Input, Select } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

type Court = {
  id: number;
  branch_id: number;
  name: string;
  surface_type: string | null;
  rental_cost: number | null;
  status?: string;
};

export function CourtForm({ court, defaultBranchId }: { court?: Court; defaultBranchId?: number }) {
  const action = court ? updateCourtAction.bind(null, court.id) : createCourtAction;
  const [state, formAction, pending] = useActionState(action, undefined);
  const fieldErrors = state?.fieldErrors ?? {};

  return (
    <form action={formAction} className="flex flex-col gap-4">
      {state?.error && <Alert tone="crit">{state.error}</Alert>}
      <input type="hidden" name="branch_id" value={court?.branch_id ?? defaultBranchId ?? ""} />
      <Input label="Nama lapangan" name="name" required defaultValue={court?.name} error={fieldErrors.name} />
      <Select label="Jenis permukaan" name="surface_type" defaultValue={court?.surface_type ?? ""}>
        <option value="">Pilih</option>
        <option value="hard">Hard Court</option>
        <option value="clay">Clay Court</option>
        <option value="grass">Grass Court</option>
      </Select>
      <Input label="Biaya sewa (opsional)" name="rental_cost" type="number" min={0} defaultValue={court?.rental_cost ?? ""} />
      {court && (
        <Select label="Status" name="status" defaultValue={court.status ?? "active"}>
          <option value="active">Aktif</option>
          <option value="inactive">Nonaktif</option>
          <option value="maintenance">Perawatan</option>
        </Select>
      )}
      <Button type="submit" loading={pending} className="self-end">
        {court ? "Simpan Perubahan" : "Tambah Lapangan"}
      </Button>
    </form>
  );
}
