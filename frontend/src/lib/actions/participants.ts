"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { serverApi } from "@/lib/server-api";
import { ApiError } from "@/lib/api-error";
import { runMutation } from "./shared";

export async function verifyParticipantAction(participantId: string, decision: "approve" | "reject"): Promise<void> {
  await runMutation(`/portal/peserta/${participantId}`, async () => {
    await serverApi(`/participants/${participantId}/verify`, { method: "POST", body: { action: decision } });
    revalidatePath(`/portal/peserta/${participantId}`);
    revalidatePath("/portal/peserta");
  });
}

export type ParticipantFormState = { error?: string; fieldErrors?: Record<string, string> } | undefined;

export async function updateParticipantAction(
  participantId: string,
  _prevState: ParticipantFormState,
  formData: FormData,
): Promise<ParticipantFormState> {
  const payload = {
    full_name: String(formData.get("full_name") ?? ""),
    birth_date: String(formData.get("birth_date") ?? ""),
    gender: String(formData.get("gender") ?? "") || undefined,
    skill_level: String(formData.get("skill_level") ?? "") || undefined,
    phone: String(formData.get("phone") ?? "") || undefined,
    address: String(formData.get("address") ?? "") || undefined,
  };

  try {
    await serverApi(`/participants/${participantId}`, { method: "PATCH", body: payload });
  } catch (error) {
    if (error instanceof ApiError) {
      return { error: error.message, fieldErrors: error.fieldErrors() };
    }
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }

  revalidatePath(`/portal/peserta/${participantId}`);
  redirect(`/portal/peserta/${participantId}`);
}
