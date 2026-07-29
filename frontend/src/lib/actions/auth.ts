"use server";

import { redirect } from "next/navigation";
import { serverApi } from "@/lib/server-api";
import { createSession, deleteSession, type SessionUser } from "@/lib/session";
import { ApiError } from "@/lib/api-error";

type AuthResponse = { user: SessionUser; token: string };

export type FormState = { error?: string; fieldErrors?: Record<string, string> } | undefined;

export async function loginAction(_prevState: FormState, formData: FormData): Promise<FormState> {
  const email = String(formData.get("email") ?? "").trim();
  const password = String(formData.get("password") ?? "");
  const next = String(formData.get("next") ?? "");

  if (!email || !password) {
    return { error: "Email dan kata sandi wajib diisi." };
  }

  let response: AuthResponse;
  try {
    response = await serverApi<AuthResponse>("/auth/login", {
      method: "POST",
      body: { email, password, device_name: "web-portal" },
    });
  } catch (error) {
    if (error instanceof ApiError) {
      return {
        error: error.status === 422 ? "Email atau kata sandi salah, atau akun tidak aktif." : error.message,
        fieldErrors: error.fieldErrors(),
      };
    }
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }

  await createSession({ token: response.token, user: response.user });

  redirect(next && next.startsWith("/portal") ? next : "/portal/dashboard");
}

export async function logoutAction(): Promise<void> {
  try {
    await serverApi("/auth/logout", { method: "POST" });
  } catch {
    // Session cookie gets cleared below regardless — a failed revoke call
    // (e.g. token already expired) shouldn't strand the user logged in on
    // the client while the backend thinks they're already logged out.
  } finally {
    await deleteSession();
  }

  redirect("/login");
}
