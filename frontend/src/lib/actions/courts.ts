"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { serverApi } from "@/lib/server-api";
import { ApiError } from "@/lib/api-error";
import { runMutation } from "./shared";

export type CourtFormState = { error?: string; fieldErrors?: Record<string, string> } | undefined;

function payloadFrom(formData: FormData) {
  return {
    branch_id: formData.get("branch_id") ? Number(formData.get("branch_id")) : undefined,
    name: String(formData.get("name") ?? ""),
    surface_type: String(formData.get("surface_type") ?? "") || undefined,
    rental_cost: formData.get("rental_cost") ? Number(formData.get("rental_cost")) : undefined,
    status: String(formData.get("status") ?? "") || undefined,
  };
}

export async function createCourtAction(_prevState: CourtFormState, formData: FormData): Promise<CourtFormState> {
  try {
    await serverApi("/courts", { method: "POST", body: payloadFrom(formData) });
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }
  revalidatePath("/portal/lapangan");
  redirect("/portal/lapangan");
}

export async function updateCourtAction(courtId: number, _prevState: CourtFormState, formData: FormData): Promise<CourtFormState> {
  try {
    await serverApi(`/courts/${courtId}`, { method: "PUT", body: payloadFrom(formData) });
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }
  revalidatePath("/portal/lapangan");
  redirect("/portal/lapangan");
}

export async function deleteCourtAction(courtId: number): Promise<void> {
  await runMutation("/portal/lapangan", async () => {
    await serverApi(`/courts/${courtId}`, { method: "DELETE" });
    revalidatePath("/portal/lapangan");
  });
}
