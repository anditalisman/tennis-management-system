"use server";

import { redirect } from "next/navigation";
import { ApiError } from "@/lib/api-error";

/**
 * For one-click admin mutations (approve/cancel/publish/etc.) that don't need
 * client-side form state — runs the mutation, then redirects back to
 * `backTo` with `?error=` on failure so the page can render an Alert.
 */
export async function runMutation(backTo: string, fn: () => Promise<unknown>): Promise<void> {
  try {
    await fn();
  } catch (error) {
    const message = error instanceof ApiError ? error.message : "Terjadi kesalahan. Coba lagi.";
    redirect(`${backTo}${backTo.includes("?") ? "&" : "?"}error=${encodeURIComponent(message)}`);
  }
  redirect(backTo);
}
