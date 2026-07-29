"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { serverApi } from "@/lib/server-api";
import { ApiError } from "@/lib/api-error";
import { runMutation } from "./shared";

export type ClassFormState = { error?: string; fieldErrors?: Record<string, string> } | undefined;

function payloadFrom(formData: FormData) {
  return {
    program_id: Number(formData.get("program_id")),
    branch_id: formData.get("branch_id") ? Number(formData.get("branch_id")) : undefined,
    coach_id: formData.get("coach_id") ? Number(formData.get("coach_id")) : undefined,
    court_id: formData.get("court_id") ? Number(formData.get("court_id")) : undefined,
    name: String(formData.get("name") ?? ""),
    capacity_min: formData.get("capacity_min") ? Number(formData.get("capacity_min")) : undefined,
    capacity_max: Number(formData.get("capacity_max")),
    session_duration: formData.get("session_duration") ? Number(formData.get("session_duration")) : undefined,
    status: String(formData.get("status") ?? "") || undefined,
  };
}

export async function createClassAction(_prevState: ClassFormState, formData: FormData): Promise<ClassFormState> {
  try {
    await serverApi("/classes", { method: "POST", body: payloadFrom(formData) });
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }
  revalidatePath("/portal/kelas");
  redirect("/portal/kelas");
}

export async function updateClassAction(
  classId: number,
  _prevState: ClassFormState,
  formData: FormData,
): Promise<ClassFormState> {
  try {
    await serverApi(`/classes/${classId}`, { method: "PUT", body: payloadFrom(formData) });
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }
  revalidatePath("/portal/kelas");
  redirect(`/portal/kelas/${classId}`);
}

export async function deleteClassAction(classId: number): Promise<void> {
  await runMutation("/portal/kelas", async () => {
    await serverApi(`/classes/${classId}`, { method: "DELETE" });
    revalidatePath("/portal/kelas");
  });
}

export async function enrollParticipantAction(classId: number, formData: FormData): Promise<void> {
  const participantId = String(formData.get("participant_id") ?? "");
  await runMutation(`/portal/kelas/${classId}`, async () => {
    await serverApi(`/classes/${classId}/members`, { method: "POST", body: { participant_id: participantId } });
    revalidatePath(`/portal/kelas/${classId}`);
  });
}

export async function removeMemberAction(classId: number, participantId: string): Promise<void> {
  await runMutation(`/portal/kelas/${classId}`, async () => {
    await serverApi(`/classes/${classId}/members/${participantId}`, { method: "DELETE" });
    revalidatePath(`/portal/kelas/${classId}`);
  });
}

export async function bookTrialClassAction(classId: number, formData: FormData): Promise<void> {
  const participantId = String(formData.get("participant_id") ?? "");
  const trialDate = String(formData.get("trial_date") ?? "");
  await runMutation(`/portal/kelas/${classId}`, async () => {
    await serverApi(`/classes/${classId}/trial`, { method: "POST", body: { participant_id: participantId, trial_date: trialDate } });
    revalidatePath(`/portal/kelas/${classId}`);
  });
}

export async function convertTrialClassAction(classId: number, trialId: number): Promise<void> {
  await runMutation(`/portal/kelas/${classId}`, async () => {
    await serverApi(`/trial-classes/${trialId}/convert`, { method: "POST" });
    revalidatePath(`/portal/kelas/${classId}`);
  });
}
