"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { serverApi } from "@/lib/server-api";
import { ApiError } from "@/lib/api-error";
import { runMutation } from "./shared";

export type PackageFormState = { error?: string; fieldErrors?: Record<string, string> } | undefined;

function payloadFrom(formData: FormData) {
  return {
    name: String(formData.get("name") ?? ""),
    session_count: Number(formData.get("session_count")),
    validity_days: formData.get("validity_days") ? Number(formData.get("validity_days")) : undefined,
    price: Number(formData.get("price")),
    status: String(formData.get("status") ?? "") || undefined,
  };
}

export async function createPackageAction(_prevState: PackageFormState, formData: FormData): Promise<PackageFormState> {
  try {
    await serverApi("/packages", { method: "POST", body: payloadFrom(formData) });
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }
  revalidatePath("/portal/paket");
  redirect("/portal/paket");
}

export async function updatePackageAction(
  packageId: number,
  _prevState: PackageFormState,
  formData: FormData,
): Promise<PackageFormState> {
  try {
    await serverApi(`/packages/${packageId}`, { method: "PUT", body: payloadFrom(formData) });
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }
  revalidatePath("/portal/paket");
  redirect("/portal/paket");
}

export async function deletePackageAction(packageId: number): Promise<void> {
  await runMutation("/portal/paket", async () => {
    await serverApi(`/packages/${packageId}`, { method: "DELETE" });
    revalidatePath("/portal/paket");
  });
}

export type CheckoutFormState = { error?: string; fieldErrors?: Record<string, string> } | undefined;

export async function checkoutPackageAction(
  packageId: number,
  _prevState: CheckoutFormState,
  formData: FormData,
): Promise<CheckoutFormState> {
  const participantId = String(formData.get("participant_id") ?? "") || undefined;
  const voucherCode = String(formData.get("voucher_code") ?? "") || undefined;

  let invoiceId: number;
  try {
    invoiceId = await serverApi<{ id: number }>(`/packages/${packageId}/checkout`, {
      method: "POST",
      body: { participant_id: participantId, voucher_code: voucherCode },
    }).then((i) => i.id);
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }

  revalidatePath("/portal/tagihan");
  redirect(`/portal/tagihan/${invoiceId}`);
}
