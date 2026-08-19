"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { serverApi } from "@/lib/server-api";
import { ApiError } from "@/lib/api-error";
import { runMutation } from "./shared";

export type TournamentRegistrationState = { error?: string; fieldErrors?: Record<string, string> } | undefined;

export async function registerTournamentParticipantAction(
  _prevState: TournamentRegistrationState,
  formData: FormData,
): Promise<TournamentRegistrationState> {
  const payload = {
    full_name: String(formData.get("full_name") ?? ""),
    nickname: String(formData.get("nickname") ?? ""),
    phone: String(formData.get("phone") ?? ""),
    category: String(formData.get("category") ?? ""),
  };

  try {
    await serverApi("/tournament/participants", { method: "POST", body: payload });
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }

  revalidatePath("/turnamen-kemerdekaan");
  redirect("/turnamen-kemerdekaan?terdaftar=1");
}

export async function generateTournamentDrawAction(): Promise<void> {
  await runMutation("/portal/turnamen", async () => {
    await serverApi("/tournament/draw", { method: "POST" });
    revalidatePath("/portal/turnamen");
    revalidatePath("/turnamen-kemerdekaan");
  });
}

export async function clearTournamentDrawAction(): Promise<void> {
  await runMutation("/portal/turnamen", async () => {
    await serverApi("/tournament/draw", { method: "DELETE" });
    revalidatePath("/portal/turnamen");
    revalidatePath("/turnamen-kemerdekaan");
  });
}

export async function moveTournamentParticipantCategoryAction(participantId: number, category: string): Promise<void> {
  await runMutation("/portal/turnamen", async () => {
    await serverApi(`/tournament/participants/${participantId}`, { method: "PATCH", body: { category } });
    revalidatePath("/portal/turnamen");
    revalidatePath("/turnamen-kemerdekaan");
  });
}
