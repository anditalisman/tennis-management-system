"use client";

import { useActionState, useState } from "react";
import { createEvaluationAction } from "@/lib/actions/evaluations";
import { ASPECT_LABELS } from "@/lib/evaluation-aspects";
import { api } from "@/lib/api";
import { Input, Select, Textarea } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

type Option = { id: number; name: string };
type Member = { id: string; full_name: string; registration_no: string };

export function EvaluationForm({ classes, coaches }: { classes: Option[]; coaches: Option[] | null }) {
  const [state, formAction, pending] = useActionState(createEvaluationAction, undefined);
  const [classId, setClassId] = useState("");
  const [members, setMembers] = useState<Member[]>([]);
  const [loadingMembers, setLoadingMembers] = useState(false);

  async function handleClassChange(nextClassId: string) {
    setClassId(nextClassId);
    setMembers([]);
    if (!nextClassId) return;

    setLoadingMembers(true);
    try {
      setMembers(await api<Member[]>(`/classes/${nextClassId}/members`));
    } catch {
      setMembers([]);
    } finally {
      setLoadingMembers(false);
    }
  }

  const fieldErrors = state?.fieldErrors ?? {};

  return (
    <form action={formAction} className="flex flex-col gap-4">
      {state?.error && <Alert tone="crit">{state.error}</Alert>}
      {coaches && (
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
      )}
      <Select label="Kelas" value={classId} onChange={(e) => handleClassChange(e.target.value)} required>
        <option value="" disabled>
          Pilih kelas
        </option>
        {classes.map((c) => (
          <option key={c.id} value={c.id}>
            {c.name}
          </option>
        ))}
      </Select>

      <Select label="Peserta" name="participant_id" required disabled={!classId || loadingMembers} error={fieldErrors.participant_id} defaultValue="">
        <option value="" disabled>
          {loadingMembers ? "Memuat peserta..." : "Pilih peserta"}
        </option>
        {members.map((m) => (
          <option key={m.id} value={m.id}>
            {m.full_name} ({m.registration_no})
          </option>
        ))}
      </Select>

      <Input label="Tanggal evaluasi" name="evaluation_date" type="date" required defaultValue={new Date().toISOString().slice(0, 10)} />

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        {Object.entries(ASPECT_LABELS).map(([aspect, label]) => (
          <div key={aspect} className="flex items-center justify-between gap-3 rounded-lg border border-(--color-ink-900)/10 px-3.5 py-2.5">
            <label htmlFor={`score_${aspect}`} className="text-sm font-medium text-(--color-ink-900)">
              {label}
            </label>
            <select
              id={`score_${aspect}`}
              name={`score_${aspect}`}
              defaultValue={3}
              className="rounded-md border border-(--color-ink-900)/15 bg-(--color-paper) px-2 py-1 text-sm"
            >
              {[1, 2, 3, 4, 5].map((n) => (
                <option key={n} value={n}>
                  {n}
                </option>
              ))}
            </select>
          </div>
        ))}
      </div>

      <Textarea label="Target berikutnya (opsional)" name="next_target" />

      <Button type="submit" loading={pending} className="self-end">
        Simpan Evaluasi
      </Button>
    </form>
  );
}
