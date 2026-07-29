"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { serverApi } from "@/lib/server-api";
import { ApiError } from "@/lib/api-error";
import { runMutation } from "./shared";

export type AnnouncementFormState = { error?: string; fieldErrors?: Record<string, string> } | undefined;

function payloadFrom(formData: FormData) {
  const targetType = String(formData.get("target_type") ?? "all");
  return {
    title: String(formData.get("title") ?? ""),
    body: String(formData.get("body") ?? ""),
    target_type: targetType,
    target_id: targetType !== "all" && formData.get("target_id") ? Number(formData.get("target_id")) : undefined,
    status: String(formData.get("status") ?? "") || undefined,
  };
}

export async function createAnnouncementAction(_prevState: AnnouncementFormState, formData: FormData): Promise<AnnouncementFormState> {
  try {
    await serverApi("/announcements", { method: "POST", body: payloadFrom(formData) });
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }
  revalidatePath("/portal/pengumuman");
  redirect("/portal/pengumuman");
}

export async function deleteAnnouncementAction(announcementId: number): Promise<void> {
  await runMutation("/portal/pengumuman", async () => {
    await serverApi(`/announcements/${announcementId}`, { method: "DELETE" });
    revalidatePath("/portal/pengumuman");
  });
}
