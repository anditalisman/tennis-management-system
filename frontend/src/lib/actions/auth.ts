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
      const fieldErrors = error.fieldErrors();
      // /auth/login only ever fails with an `email`-keyed message (wrong
      // credentials, inactive account, or unverified email) — surface it
      // directly instead of a generic string so "belum diverifikasi" isn't
      // flattened into a misleading "wrong password" message.
      return { error: fieldErrors.email ?? error.message, fieldErrors };
    }
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }

  await createSession({ token: response.token, user: response.user });

  redirect(next && next.startsWith("/portal") ? next : "/portal/dashboard");
}

export type ResendState = { message?: string; error?: string } | undefined;

export async function resendVerificationAction(_prevState: ResendState, formData: FormData): Promise<ResendState> {
  const email = String(formData.get("email") ?? "").trim();
  if (!email) {
    return { error: "Masukkan email Anda terlebih dahulu." };
  }

  try {
    const result = await serverApi<{ message: string }>("/auth/verify-email/resend", {
      method: "POST",
      body: { email },
    });
    return { message: result.message };
  } catch (error) {
    return { error: error instanceof ApiError ? error.message : "Tidak dapat terhubung ke server. Coba lagi." };
  }
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
