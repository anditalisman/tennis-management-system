"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { serverApi } from "@/lib/server-api";
import { ApiError } from "@/lib/api-error";
import { runMutation } from "./shared";

export type ScheduleFormState = { error?: string; fieldErrors?: Record<string, string> } | undefined;

function payloadFrom(formData: FormData) {
  return {
    class_id: Number(formData.get("class_id")),
    court_id: Number(formData.get("court_id")),
    coach_id: Number(formData.get("coach_id")),
    session_date: String(formData.get("session_date") ?? ""),
    start_time: String(formData.get("start_time") ?? ""),
    end_time: String(formData.get("end_time") ?? ""),
    type: String(formData.get("type") ?? "") || undefined,
  };
}

export async function createScheduleAction(_prevState: ScheduleFormState, formData: FormData): Promise<ScheduleFormState> {
  try {
    await serverApi("/schedules", { method: "POST", body: payloadFrom(formData) });
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }
  revalidatePath("/portal/jadwal");
  redirect("/portal/jadwal");
}

export async function cancelScheduleAction(scheduleId: number, formData: FormData): Promise<void> {
  const reason = String(formData.get("reason") ?? "");
  await runMutation(`/portal/jadwal/${scheduleId}`, async () => {
    await serverApi(`/schedules/${scheduleId}/cancel`, { method: "POST", body: { reason } });
    revalidatePath(`/portal/jadwal/${scheduleId}`);
    revalidatePath("/portal/jadwal");
  });
}

export async function rescheduleAction(
  scheduleId: number,
  _prevState: ScheduleFormState,
  formData: FormData,
): Promise<ScheduleFormState> {
  const payload = {
    court_id: Number(formData.get("court_id")),
    coach_id: Number(formData.get("coach_id")),
    session_date: String(formData.get("session_date") ?? ""),
    start_time: String(formData.get("start_time") ?? ""),
    end_time: String(formData.get("end_time") ?? ""),
  };

  let newId: number;
  try {
    newId = await serverApi<{ id: number }>(`/schedules/${scheduleId}/reschedule`, { method: "POST", body: payload }).then((s) => s.id);
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }
  revalidatePath("/portal/jadwal");
  redirect(`/portal/jadwal/${newId}`);
}

export async function checkInAction(scheduleId: number): Promise<void> {
  await runMutation(`/portal/jadwal/${scheduleId}`, async () => {
    await serverApi(`/schedules/${scheduleId}/check-in`, { method: "POST", body: {} });
    revalidatePath(`/portal/jadwal/${scheduleId}`);
  });
}
