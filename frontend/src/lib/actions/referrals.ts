"use server";

import { revalidatePath } from "next/cache";
import { serverApi } from "@/lib/server-api";
import { runMutation } from "./shared";

export async function generateReferralCodeAction(participantId: string): Promise<void> {
  await runMutation(`/portal/peserta/${participantId}`, async () => {
    await serverApi(`/participants/${participantId}/referral-code`, { method: "POST" });
    revalidatePath(`/portal/peserta/${participantId}`);
  });
}
