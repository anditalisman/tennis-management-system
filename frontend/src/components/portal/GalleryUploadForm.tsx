"use client";

import { useActionState } from "react";
import { createGalleryAction } from "@/lib/actions/galleries";
import { Input, Select } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

type Option = { id: number; name: string };

export function GalleryUploadForm({ classes }: { classes: Option[] }) {
  const [state, formAction, pending] = useActionState(createGalleryAction, undefined);

  return (
    <form action={formAction} className="flex flex-col gap-4">
      {state?.error && <Alert tone="crit">{state.error}</Alert>}
      <Select label="Kelas" name="class_id" required defaultValue="">
        <option value="" disabled>
          Pilih kelas
        </option>
        {classes.map((c) => (
          <option key={c.id} value={c.id}>
            {c.name}
          </option>
        ))}
      </Select>
      <Input label="Judul (opsional)" name="title" />
      <div className="flex flex-col gap-1.5">
        <label className="text-sm font-semibold text-(--color-ink-900)" htmlFor="files">
          Foto / video
        </label>
        <input
          id="files"
          name="files"
          type="file"
          multiple
          accept="image/*,video/*"
          className="rounded-lg border border-(--color-ink-900)/15 bg-(--color-paper-raised) px-3.5 py-2.5 text-sm text-(--color-ink-900) file:mr-3 file:rounded-full file:border-0 file:bg-(--color-court-600) file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white"
        />
        <p className="text-xs text-(--color-ink-500)">Galeri akan menunggu moderasi admin sebelum tampil di situs publik.</p>
      </div>
      <Button type="submit" loading={pending} className="self-end">
        Unggah Galeri
      </Button>
    </form>
  );
}
