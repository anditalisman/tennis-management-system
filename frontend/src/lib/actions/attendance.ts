"use server";

import { revalidatePath } from "next/cache";
import { serverApi } from "@/lib/server-api";
import { runMutation } from "./shared";

export async function bulkVerifyAttendanceAction(scheduleId: number, formData: FormData): Promise<void> {
  const participantIds = formData.getAll("participant_id") as string[];
  const records = participantIds.map((participantId) => ({
    participant_id: participantId,
    status: String(formData.get(`status_${participantId}`) ?? "present"),
  }));

  await runMutation(`/portal/jadwal/${scheduleId}`, async () => {
    await serverApi(`/schedules/${scheduleId}/attendance`, { method: "POST", body: { records } });
    revalidatePath(`/portal/jadwal/${scheduleId}`);
  });
}
