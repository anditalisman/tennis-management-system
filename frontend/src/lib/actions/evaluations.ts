"use server";

import { redirect } from "next/navigation";
import { serverApi } from "@/lib/server-api";
import { ApiError } from "@/lib/api-error";
import { ASPECT_LABELS } from "@/lib/evaluation-aspects";

export type EvaluationFormState = { error?: string; fieldErrors?: Record<string, string> } | undefined;

export async function createEvaluationAction(_prevState: EvaluationFormState, formData: FormData): Promise<EvaluationFormState> {
  const participantId = String(formData.get("participant_id") ?? "");
  const details = Object.keys(ASPECT_LABELS).map((aspect) => ({
    aspect,
    score: Number(formData.get(`score_${aspect}`) ?? 3),
  }));

  const payload = {
    participant_id: participantId,
    coach_id: formData.get("coach_id") ? Number(formData.get("coach_id")) : undefined,
    evaluation_date: String(formData.get("evaluation_date") ?? ""),
    next_target: String(formData.get("next_target") ?? "") || undefined,
    details,
  };

  try {
    await serverApi("/evaluations", { method: "POST", body: payload });
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }

  redirect(`/portal/peserta/${participantId}`);
}
