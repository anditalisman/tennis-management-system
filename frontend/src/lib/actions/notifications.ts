"use server";

import { revalidatePath } from "next/cache";
import { serverApi } from "@/lib/server-api";

export async function markNotificationReadAction(notificationId: number): Promise<void> {
  await serverApi(`/notifications/${notificationId}/read`, { method: "POST" });
  revalidatePath("/portal/notifikasi");
}
