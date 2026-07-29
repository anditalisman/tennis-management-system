"use client";

import { useActionState } from "react";
import { createScheduleAction } from "@/lib/actions/schedules";
import { Input, Select } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

type Option = { id: number; name: string };

export function ScheduleForm({
  classes,
  courts,
  coaches,
}: {
  classes: Option[];
  courts: Option[];
  coaches: Option[];
}) {
  const [state, formAction, pending] = useActionState(createScheduleAction, undefined);
  const fieldErrors = state?.fieldErrors ?? {};

  return (
    <form action={formAction} className="flex flex-col gap-4">
      {state?.error && <Alert tone="crit">{state.error}</Alert>}
      <Select label="Kelas" name="class_id" required defaultValue="" error={fieldErrors.class_id}>
        <option value="" disabled>
          Pilih kelas
        </option>
        {classes.map((c) => (
          <option key={c.id} value={c.id}>
            {c.name}
          </option>
        ))}
      </Select>
      <div className="grid grid-cols-2 gap-4">
        <Select label="Lapangan" name="court_id" required defaultValue="" error={fieldErrors.court_id}>
          <option value="" disabled>
            Pilih lapangan
          </option>
          {courts.map((c) => (
            <option key={c.id} value={c.id}>
              {c.name}
            </option>
          ))}
        </Select>
        <Select label="Pelatih" name="coach_id" required defaultValue="" error={fieldErrors.coach_id}>
          <option value="" disabled>
            Pilih pelatih
          </option>
          {coaches.map((c) => (
            <option key={c.id} value={c.id}>
              {c.name}
            </option>
          ))}
        </Select>
      </div>
      <Input label="Tanggal sesi" name="session_date" type="date" required error={fieldErrors.session_date} />
      <div className="grid grid-cols-2 gap-4">
        <Input label="Jam mulai" name="start_time" type="time" required error={fieldErrors.start_time} />
        <Input label="Jam selesai" name="end_time" type="time" required error={fieldErrors.end_time} />
      </div>
      <Select label="Tipe sesi" name="type" defaultValue="reguler">
        <option value="reguler">Reguler</option>
        <option value="khusus">Khusus</option>
      </Select>
      <Button type="submit" loading={pending} className="self-end">
        Buat Jadwal
      </Button>
    </form>
  );
}
