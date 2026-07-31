"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { getSession } from "@/lib/session";
import { serverApi } from "@/lib/server-api";
import { runMutation } from "./shared";

export type PaymentMethodFormState = { error?: string; fieldErrors?: Record<string, string> } | undefined;

const API_INTERNAL_URL = process.env.API_INTERNAL_URL ?? "http://nginx/api/v1";

function buildForm(formData: FormData): FormData {
  const upstream = new FormData();
  upstream.append("type", String(formData.get("type") ?? ""));
  upstream.append("label", String(formData.get("label") ?? ""));
  const details = String(formData.get("details") ?? "");
  if (details) upstream.append("details", details);
  upstream.append("is_active", formData.get("is_active") === "1" ? "1" : "0");
  const image = formData.get("image");
  if (image instanceof File && image.size > 0) upstream.append("image", image);
  return upstream;
}

async function submitToBackend(path: string, formData: FormData): Promise<PaymentMethodFormState> {
  const session = await getSession();
  const res = await fetch(`${API_INTERNAL_URL}${path}`, {
    method: "POST",
    headers: { Accept: "application/json", ...(session ? { Authorization: `Bearer ${session.token}` } : {}) },
    body: buildForm(formData),
  });

  if (!res.ok) {
    const payload = await res.json().catch(() => undefined);
    return {
      error: payload?.message ?? "Gagal menyimpan metode pembayaran.",
      fieldErrors: payload?.errors
        ? Object.fromEntries(Object.entries(payload.errors).map(([k, v]) => [k, (v as string[])[0]]))
        : undefined,
    };
  }

  return undefined;
}

export async function createPaymentMethodAction(
  _prevState: PaymentMethodFormState,
  formData: FormData,
): Promise<PaymentMethodFormState> {
  const result = await submitToBackend("/payment-methods", formData);
  if (result) return result;

  revalidatePath("/portal/pengaturan/metode-pembayaran");
  redirect("/portal/pengaturan/metode-pembayaran");
}

export async function updatePaymentMethodAction(
  methodId: number,
  _prevState: PaymentMethodFormState,
  formData: FormData,
): Promise<PaymentMethodFormState> {
  const result = await submitToBackend(`/payment-methods/${methodId}`, formData);
  if (result) return result;

  revalidatePath("/portal/pengaturan/metode-pembayaran");
  redirect("/portal/pengaturan/metode-pembayaran");
}

export async function deletePaymentMethodAction(methodId: number): Promise<void> {
  await runMutation("/portal/pengaturan/metode-pembayaran", async () => {
    await serverApi(`/payment-methods/${methodId}`, { method: "DELETE" });
    revalidatePath("/portal/pengaturan/metode-pembayaran");
  });
}
