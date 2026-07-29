"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { serverApi } from "@/lib/server-api";
import { ApiError } from "@/lib/api-error";
import { runMutation } from "./shared";

export type UserFormState = { error?: string; fieldErrors?: Record<string, string> } | undefined;

function basePayload(formData: FormData) {
  return {
    name: String(formData.get("name") ?? ""),
    email: String(formData.get("email") ?? ""),
    phone: String(formData.get("phone") ?? "") || undefined,
    branch_id: formData.get("branch_id") ? Number(formData.get("branch_id")) : undefined,
    roles: formData.getAll("roles").map(String),
  };
}

export async function createUserAction(_prevState: UserFormState, formData: FormData): Promise<UserFormState> {
  const payload = { ...basePayload(formData), password: String(formData.get("password") ?? "") };

  try {
    await serverApi("/users", { method: "POST", body: payload });
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }
  revalidatePath("/portal/pengaturan/pengguna");
  redirect("/portal/pengaturan/pengguna");
}

export async function updateUserAction(userId: string, _prevState: UserFormState, formData: FormData): Promise<UserFormState> {
  const password = String(formData.get("password") ?? "");
  const payload = {
    ...basePayload(formData),
    status: String(formData.get("status") ?? "") || undefined,
    ...(password ? { password } : {}),
  };

  try {
    await serverApi(`/users/${userId}`, { method: "PUT", body: payload });
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }
  revalidatePath("/portal/pengaturan/pengguna");
  redirect("/portal/pengaturan/pengguna");
}

export async function deactivateUserAction(userId: string): Promise<void> {
  await runMutation("/portal/pengaturan/pengguna", async () => {
    await serverApi(`/users/${userId}`, { method: "DELETE" });
    revalidatePath("/portal/pengaturan/pengguna");
  });
}
