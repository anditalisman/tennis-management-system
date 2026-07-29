"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { serverApi } from "@/lib/server-api";
import { ApiError } from "@/lib/api-error";

export type VoucherFormState = { error?: string; fieldErrors?: Record<string, string> } | undefined;

export async function createVoucherAction(_prevState: VoucherFormState, formData: FormData): Promise<VoucherFormState> {
  const payload = {
    code: String(formData.get("code") ?? "").toUpperCase(),
    discount_type: String(formData.get("discount_type") ?? ""),
    discount_value: Number(formData.get("discount_value")),
    usage_limit: formData.get("usage_limit") ? Number(formData.get("usage_limit")) : undefined,
    valid_from: String(formData.get("valid_from") ?? "") || undefined,
    valid_until: String(formData.get("valid_until") ?? "") || undefined,
  };

  try {
    await serverApi("/vouchers", { method: "POST", body: payload });
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }

  revalidatePath("/portal/voucher");
  redirect("/portal/voucher");
}
