"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { serverApi } from "@/lib/server-api";
import { ApiError } from "@/lib/api-error";
import { runMutation } from "./shared";

export type CoachFormState = { error?: string; fieldErrors?: Record<string, string> } | undefined;

export async function createCoachAction(_prevState: CoachFormState, formData: FormData): Promise<CoachFormState> {
  const payload = {
    name: String(formData.get("name") ?? ""),
    email: String(formData.get("email") ?? ""),
    phone: String(formData.get("phone") ?? "") || undefined,
    password: String(formData.get("password") ?? ""),
    branch_id: formData.get("branch_id") ? Number(formData.get("branch_id")) : undefined,
    bio: String(formData.get("bio") ?? "") || undefined,
  };

  try {
    await serverApi("/coaches", { method: "POST", body: payload });
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }
  revalidatePath("/portal/pelatih");
  redirect("/portal/pelatih");
}

export async function updateCoachAction(coachId: number, _prevState: CoachFormState, formData: FormData): Promise<CoachFormState> {
  const payload = {
    branch_id: formData.get("branch_id") ? Number(formData.get("branch_id")) : undefined,
    bio: String(formData.get("bio") ?? "") || undefined,
    employment_status: String(formData.get("employment_status") ?? "") || undefined,
  };

  try {
    await serverApi(`/coaches/${coachId}`, { method: "PUT", body: payload });
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }
  revalidatePath("/portal/pelatih");
  redirect("/portal/pelatih");
}

export async function deleteCoachAction(coachId: number): Promise<void> {
  await runMutation("/portal/pelatih", async () => {
    await serverApi(`/coaches/${coachId}`, { method: "DELETE" });
    revalidatePath("/portal/pelatih");
  });
}
