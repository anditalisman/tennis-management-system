"use client";

import { useActionState } from "react";
import { updateParticipantAction, type ParticipantFormState } from "@/lib/actions/participants";
import { Input, Select, Textarea } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

type Participant = {
  id: string;
  full_name: string;
  birth_date: string | null;
  gender: string | null;
  skill_level: string | null;
  phone: string | null;
  address: string | null;
};

export function EditParticipantForm({ participant }: { participant: Participant }) {
  const action = updateParticipantAction.bind(null, participant.id) as (
    state: ParticipantFormState,
    formData: FormData,
  ) => Promise<ParticipantFormState>;
  const [state, formAction, pending] = useActionState(action, undefined);
  const fieldErrors = state?.fieldErrors ?? {};

  return (
    <form action={formAction} className="flex flex-col gap-4">
      {state?.error && <Alert tone="crit">{state.error}</Alert>}
      <Input label="Nama lengkap" name="full_name" required defaultValue={participant.full_name} error={fieldErrors.full_name} />
      <div className="grid grid-cols-2 gap-4">
        <Input
          label="Tanggal lahir"
          name="birth_date"
          type="date"
          required
          defaultValue={participant.birth_date ?? ""}
          error={fieldErrors.birth_date}
        />
        <Select label="Jenis kelamin" name="gender" defaultValue={participant.gender ?? ""}>
          <option value="">Pilih</option>
          <option value="male">Laki-laki</option>
          <option value="female">Perempuan</option>
        </Select>
      </div>
      <Select label="Level kemampuan" name="skill_level" defaultValue={participant.skill_level ?? "beginner"}>
        <option value="beginner">Pemula</option>
        <option value="intermediate">Menengah</option>
        <option value="advanced">Mahir</option>
      </Select>
      <Input label="Telepon" name="phone" type="tel" defaultValue={participant.phone ?? ""} />
      <Textarea label="Alamat" name="address" defaultValue={participant.address ?? ""} />
      <Button type="submit" loading={pending} className="self-end">
        Simpan Perubahan
      </Button>
    </form>
  );
}
