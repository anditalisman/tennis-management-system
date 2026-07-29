"use client";

import { useActionState } from "react";
import { createCoachAction, updateCoachAction } from "@/lib/actions/coaches";
import { Input, Select, Textarea } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

type Coach = {
  id: number;
  name: string | null;
  branch_id: number | null;
  bio: string | null;
  employment_status?: string;
};

export function CoachForm({ coach }: { coach?: Coach }) {
  const action = coach ? updateCoachAction.bind(null, coach.id) : createCoachAction;
  const [state, formAction, pending] = useActionState(action, undefined);
  const fieldErrors = state?.fieldErrors ?? {};

  return (
    <form action={formAction} className="flex flex-col gap-4">
      {state?.error && <Alert tone="crit">{state.error}</Alert>}
      {!coach && (
        <>
          <Input label="Nama pelatih" name="name" required error={fieldErrors.name} />
          <Input label="Email (untuk login)" name="email" type="email" required error={fieldErrors.email} />
          <Input label="Telepon" name="phone" type="tel" />
          <Input
            label="Kata sandi"
            name="password"
            type="password"
            required
            hint="Minimal 8 karakter, kombinasi huruf besar/kecil dan angka."
            error={fieldErrors.password}
          />
        </>
      )}
      <Textarea label="Bio" name="bio" defaultValue={coach?.bio ?? ""} />
      {coach && (
        <Select label="Status kepegawaian" name="employment_status" defaultValue={coach.employment_status ?? "active"}>
          <option value="active">Aktif</option>
          <option value="inactive">Nonaktif</option>
        </Select>
      )}
      <Button type="submit" loading={pending} className="self-end">
        {coach ? "Simpan Perubahan" : "Tambah Pelatih"}
      </Button>
    </form>
  );
}
