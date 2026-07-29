"use client";

import { useActionState } from "react";
import { createClassAction, updateClassAction } from "@/lib/actions/classes";
import { Input, Select } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

type Option = { id: number; name: string };

type TrainingClass = {
  id: number;
  program_id: number;
  branch_id: number;
  coach_id: number | null;
  court_id: number | null;
  name: string;
  capacity_min: number;
  capacity_max: number;
  session_duration: number;
  status?: string;
};

export function ClassForm({
  trainingClass,
  defaultBranchId,
  programs,
  coaches,
  courts,
}: {
  trainingClass?: TrainingClass;
  defaultBranchId?: number;
  programs: Option[];
  coaches: Option[];
  courts: Option[];
}) {
  const action = trainingClass ? updateClassAction.bind(null, trainingClass.id) : createClassAction;
  const [state, formAction, pending] = useActionState(action, undefined);
  const fieldErrors = state?.fieldErrors ?? {};

  return (
    <form action={formAction} className="flex flex-col gap-4">
      {state?.error && <Alert tone="crit">{state.error}</Alert>}
      <input type="hidden" name="branch_id" value={trainingClass?.branch_id ?? defaultBranchId ?? ""} />
      <Input label="Nama kelas" name="name" required defaultValue={trainingClass?.name} error={fieldErrors.name} />
      <Select label="Program" name="program_id" required defaultValue={trainingClass?.program_id ?? ""} error={fieldErrors.program_id}>
        <option value="" disabled>
          Pilih program
        </option>
        {programs.map((p) => (
          <option key={p.id} value={p.id}>
            {p.name}
          </option>
        ))}
      </Select>
      <div className="grid grid-cols-2 gap-4">
        <Select label="Pelatih" name="coach_id" defaultValue={trainingClass?.coach_id ?? ""}>
          <option value="">Belum ditentukan</option>
          {coaches.map((c) => (
            <option key={c.id} value={c.id}>
              {c.name}
            </option>
          ))}
        </Select>
        <Select label="Lapangan" name="court_id" defaultValue={trainingClass?.court_id ?? ""}>
          <option value="">Belum ditentukan</option>
          {courts.map((c) => (
            <option key={c.id} value={c.id}>
              {c.name}
            </option>
          ))}
        </Select>
      </div>
      <div className="grid grid-cols-3 gap-4">
        <Input label="Kapasitas min." name="capacity_min" type="number" min={1} defaultValue={trainingClass?.capacity_min ?? 1} />
        <Input
          label="Kapasitas maks."
          name="capacity_max"
          type="number"
          min={1}
          required
          defaultValue={trainingClass?.capacity_max ?? 8}
          error={fieldErrors.capacity_max}
        />
        <Input
          label="Durasi (menit)"
          name="session_duration"
          type="number"
          min={15}
          max={240}
          defaultValue={trainingClass?.session_duration ?? 60}
        />
      </div>
      {trainingClass && (
        <Select label="Status" name="status" defaultValue={trainingClass.status ?? "active"}>
          <option value="active">Aktif</option>
          <option value="inactive">Nonaktif</option>
        </Select>
      )}
      <Button type="submit" loading={pending} className="self-end">
        {trainingClass ? "Simpan Perubahan" : "Buat Kelas"}
      </Button>
    </form>
  );
}
