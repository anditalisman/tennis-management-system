"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { randomUUID } from "crypto";
import { getSession } from "@/lib/session";
import { serverApi } from "@/lib/server-api";
import { ApiError } from "@/lib/api-error";
import { runMutation } from "./shared";

export type InvoiceFormState = { error?: string; fieldErrors?: Record<string, string> } | undefined;

const API_INTERNAL_URL = process.env.API_INTERNAL_URL ?? "http://nginx/api/v1";

export async function createInvoiceAction(_prevState: InvoiceFormState, formData: FormData): Promise<InvoiceFormState> {
  const itemType = String(formData.get("item_type") ?? "package");
  const item =
    itemType === "package"
      ? { item_type: "package", package_id: Number(formData.get("package_id")) }
      : {
          item_type: "other",
          description: String(formData.get("description") ?? ""),
          unit_price: Number(formData.get("unit_price")),
        };

  const payload = {
    participant_id: String(formData.get("participant_id") ?? ""),
    due_date: String(formData.get("due_date") ?? ""),
    discount_amount: formData.get("discount_amount") ? Number(formData.get("discount_amount")) : undefined,
    voucher_code: String(formData.get("voucher_code") ?? "") || undefined,
    items: [item],
  };

  let invoiceId: number;
  try {
    invoiceId = await serverApi<{ id: number }>("/invoices", { method: "POST", body: payload }).then((i) => i.id);
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }

  revalidatePath("/portal/tagihan");
  redirect(`/portal/tagihan/${invoiceId}`);
}

export type PaymentFormState = { error?: string; fieldErrors?: Record<string, string> } | undefined;

export async function submitPaymentAction(
  invoiceId: number,
  _prevState: PaymentFormState,
  formData: FormData,
): Promise<PaymentFormState> {
  const session = await getSession();
  const proof = formData.get("proof");

  const upstreamForm = new FormData();
  upstreamForm.append("method", String(formData.get("method") ?? ""));
  upstreamForm.append("amount", String(formData.get("amount") ?? ""));
  const referenceNo = String(formData.get("reference_no") ?? "");
  if (referenceNo) upstreamForm.append("reference_no", referenceNo);
  if (proof instanceof File && proof.size > 0) upstreamForm.append("proof", proof);

  const res = await fetch(`${API_INTERNAL_URL}/invoices/${invoiceId}/payments`, {
    method: "POST",
    headers: {
      Accept: "application/json",
      "Idempotency-Key": randomUUID(),
      ...(session ? { Authorization: `Bearer ${session.token}` } : {}),
    },
    body: upstreamForm,
  });

  const payload = await res.json().catch(() => undefined);
  if (!res.ok) {
    return { error: payload?.message ?? "Gagal mengirim pembayaran.", fieldErrors: payload?.errors ? Object.fromEntries(Object.entries(payload.errors).map(([k, v]) => [k, (v as string[])[0]])) : undefined };
  }

  revalidatePath(`/portal/tagihan/${invoiceId}`);
  redirect(`/portal/tagihan/${invoiceId}`);
}

export async function verifyPaymentAction(invoiceId: number, paymentId: number, decision: "approve" | "reject"): Promise<void> {
  await runMutation(`/portal/tagihan/${invoiceId}`, async () => {
    await serverApi(`/payments/${paymentId}/verify`, { method: "POST", body: { action: decision } });
    revalidatePath(`/portal/tagihan/${invoiceId}`);
    revalidatePath("/portal/tagihan");
  });
}
