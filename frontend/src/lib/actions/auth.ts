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

export async function resendWhatsappVerificationAction(_prevState: ResendState, formData: FormData): Promise<ResendState> {
  const email = String(formData.get("email") ?? "").trim();
  if (!email) {
    return { error: "Masukkan email Anda terlebih dahulu." };
  }

  try {
    const result = await serverApi<{ message: string }>("/auth/verify-whatsapp/resend", {
      method: "POST",
      body: { email },
    });
    return { message: result.message };
  } catch (error) {
    return { error: error instanceof ApiError ? error.message : "Tidak dapat terhubung ke server. Coba lagi." };
  }
}

export type VerifyWhatsappState = { error?: string; success?: boolean } | undefined;

export async function verifyWhatsappAction(_prevState: VerifyWhatsappState, formData: FormData): Promise<VerifyWhatsappState> {
  const email = String(formData.get("email") ?? "").trim();
  const code = String(formData.get("code") ?? "").trim();

  try {
    await serverApi("/auth/verify-whatsapp", { method: "POST", body: { email, code } });
  } catch (error) {
    return { error: error instanceof ApiError ? error.message : "Tidak dapat terhubung ke server. Coba lagi." };
  }

  return { success: true };
}

export type ForgotPasswordState = { message?: string; error?: string } | undefined;

export async function forgotPasswordAction(_prevState: ForgotPasswordState, formData: FormData): Promise<ForgotPasswordState> {
  const email = String(formData.get("email") ?? "").trim();
  if (!email) {
    return { error: "Masukkan email Anda terlebih dahulu." };
  }

  try {
    const result = await serverApi<{ message: string }>("/auth/forgot-password", { method: "POST", body: { email } });
    return { message: result.message };
  } catch (error) {
    return { error: error instanceof ApiError ? error.message : "Tidak dapat terhubung ke server. Coba lagi." };
  }
}

export type ResetPasswordState = { error?: string; fieldErrors?: Record<string, string>; success?: boolean } | undefined;

export async function resetPasswordAction(_prevState: ResetPasswordState, formData: FormData): Promise<ResetPasswordState> {
  const payload = {
    email: String(formData.get("email") ?? "").trim(),
    code: String(formData.get("code") ?? "").trim(),
    password: String(formData.get("password") ?? ""),
    password_confirmation: String(formData.get("password_confirmation") ?? ""),
  };

  try {
    await serverApi("/auth/reset-password", { method: "POST", body: payload });
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }

  return { success: true };
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
