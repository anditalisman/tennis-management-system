"use client";

import { useActionState } from "react";
import { registerTournamentParticipantAction } from "@/lib/actions/tournament";
import { Input, Select } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

export function TournamentRegistrationForm() {
  const [state, formAction, pending] = useActionState(registerTournamentParticipantAction, undefined);
  const fieldErrors = state?.fieldErrors ?? {};

  return (
    <form action={formAction} className="flex flex-col gap-4">
      {state?.error && <Alert tone="crit">{state.error}</Alert>}
      <Input label="Nama Lengkap" name="full_name" required error={fieldErrors.full_name} />
      <Input label="Nama Panggilan" name="nickname" required error={fieldErrors.nickname} />
      <Input label="Nomor HP" name="phone" type="tel" required error={fieldErrors.phone} />
      <Select label="Kategori" name="category" required defaultValue="" error={fieldErrors.category}>
        <option value="" disabled>
          Pilih kategori
        </option>
        <option value="beginner">Beginner</option>
        <option value="upper_beginner">Upper Beginner</option>
      </Select>
      <Button type="submit" loading={pending} className="mt-2 self-start">
        Daftar Sekarang
      </Button>
    </form>
  );
}
