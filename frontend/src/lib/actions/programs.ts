"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { serverApi } from "@/lib/server-api";
import { ApiError } from "@/lib/api-error";
import { runMutation } from "./shared";

export type ProgramFormState = { error?: string; fieldErrors?: Record<string, string> } | undefined;

function payloadFrom(formData: FormData) {
  return {
    name: String(formData.get("name") ?? ""),
    age_group: String(formData.get("age_group") ?? "") || undefined,
    skill_level: String(formData.get("skill_level") ?? "") || undefined,
    target_competency: String(formData.get("target_competency") ?? "") || undefined,
    description: String(formData.get("description") ?? "") || undefined,
    status: String(formData.get("status") ?? "") || undefined,
  };
}

export async function createProgramAction(_prevState: ProgramFormState, formData: FormData): Promise<ProgramFormState> {
  try {
    await serverApi("/programs", { method: "POST", body: payloadFrom(formData) });
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }
  revalidatePath("/portal/program");
  redirect("/portal/program");
}

export async function updateProgramAction(
  programId: number,
  _prevState: ProgramFormState,
  formData: FormData,
): Promise<ProgramFormState> {
  try {
    await serverApi(`/programs/${programId}`, { method: "PUT", body: payloadFrom(formData) });
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }
  revalidatePath("/portal/program");
  redirect("/portal/program");
}

export async function deleteProgramAction(programId: number): Promise<void> {
  await runMutation("/portal/program", async () => {
    await serverApi(`/programs/${programId}`, { method: "DELETE" });
    revalidatePath("/portal/program");
  });
}
