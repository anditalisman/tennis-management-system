"use client";

import { useActionState } from "react";
import { createAnnouncementAction } from "@/lib/actions/announcements";
import { Input, Select, Textarea } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

export function PengumumanForm() {
  const [state, formAction, pending] = useActionState(createAnnouncementAction, undefined);
  const fieldErrors = state?.fieldErrors ?? {};

  return (
    <form action={formAction} className="flex flex-col gap-4">
      {state?.error && <Alert tone="crit">{state.error}</Alert>}
      <Input label="Judul" name="title" required error={fieldErrors.title} />
      <Textarea label="Isi pengumuman" name="body" required rows={6} error={fieldErrors.body} />
      <Select label="Status" name="status" defaultValue="published">
        <option value="published">Terbitkan sekarang</option>
        <option value="draft">Simpan sebagai draf</option>
      </Select>
      <Button type="submit" loading={pending} className="self-end">
        Simpan Pengumuman
      </Button>
    </form>
  );
}
