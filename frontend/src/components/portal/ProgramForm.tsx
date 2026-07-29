"use client";

import { useActionState } from "react";
import { createProgramAction, updateProgramAction } from "@/lib/actions/programs";
import { Input, Select, Textarea } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

type Program = {
  id: number;
  name: string;
  age_group: string | null;
  skill_level: string | null;
  target_competency: string | null;
  description: string | null;
  status?: string;
};

export function ProgramForm({ program }: { program?: Program }) {
  const action = program ? updateProgramAction.bind(null, program.id) : createProgramAction;
  const [state, formAction, pending] = useActionState(action, undefined);
  const fieldErrors = state?.fieldErrors ?? {};

  return (
    <form action={formAction} className="flex flex-col gap-4">
      {state?.error && <Alert tone="crit">{state.error}</Alert>}
      <Input label="Nama program" name="name" required defaultValue={program?.name} error={fieldErrors.name} />
      <div className="grid grid-cols-2 gap-4">
        <Select label="Kelompok usia" name="age_group" defaultValue={program?.age_group ?? ""}>
          <option value="">Pilih</option>
          <option value="anak">Anak-anak</option>
          <option value="remaja">Remaja</option>
          <option value="dewasa">Dewasa</option>
        </Select>
        <Select label="Level" name="skill_level" defaultValue={program?.skill_level ?? ""}>
          <option value="">Pilih</option>
          <option value="beginner">Pemula</option>
          <option value="intermediate">Menengah</option>
          <option value="advanced">Mahir</option>
        </Select>
      </div>
      <Textarea label="Target kompetensi" name="target_competency" defaultValue={program?.target_competency ?? ""} />
      <Textarea label="Deskripsi" name="description" defaultValue={program?.description ?? ""} />
      {program && (
        <Select label="Status" name="status" defaultValue={program.status ?? "active"}>
          <option value="active">Aktif</option>
          <option value="inactive">Nonaktif</option>
        </Select>
      )}
      <Button type="submit" loading={pending} className="self-end">
        {program ? "Simpan Perubahan" : "Buat Program"}
      </Button>
    </form>
  );
}
