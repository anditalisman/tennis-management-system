"use server";

import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { serverApi } from "@/lib/server-api";
import { ApiError } from "@/lib/api-error";
import { runMutation } from "./shared";

export type InventoryFormState = { error?: string; fieldErrors?: Record<string, string> } | undefined;

function payloadFrom(formData: FormData) {
  return {
    branch_id: formData.get("branch_id") ? Number(formData.get("branch_id")) : undefined,
    name: String(formData.get("name") ?? ""),
    category: String(formData.get("category") ?? ""),
    stock_qty: formData.get("stock_qty") ? Number(formData.get("stock_qty")) : undefined,
    condition: String(formData.get("condition") ?? "") || undefined,
  };
}

export async function createInventoryItemAction(_prevState: InventoryFormState, formData: FormData): Promise<InventoryFormState> {
  try {
    await serverApi("/inventory-items", { method: "POST", body: payloadFrom(formData) });
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }
  revalidatePath("/portal/inventaris");
  redirect("/portal/inventaris");
}

export async function updateInventoryItemAction(
  itemId: number,
  _prevState: InventoryFormState,
  formData: FormData,
): Promise<InventoryFormState> {
  const { name, category, condition } = payloadFrom(formData);
  try {
    await serverApi(`/inventory-items/${itemId}`, { method: "PUT", body: { name, category, condition } });
  } catch (error) {
    if (error instanceof ApiError) return { error: error.message, fieldErrors: error.fieldErrors() };
    return { error: "Tidak dapat terhubung ke server. Coba lagi." };
  }
  revalidatePath("/portal/inventaris");
  redirect(`/portal/inventaris/${itemId}`);
}

export async function deleteInventoryItemAction(itemId: number): Promise<void> {
  await runMutation("/portal/inventaris", async () => {
    await serverApi(`/inventory-items/${itemId}`, { method: "DELETE" });
    revalidatePath("/portal/inventaris");
  });
}

export async function createTransactionAction(itemId: number, formData: FormData): Promise<void> {
  const payload = {
    type: String(formData.get("type") ?? ""),
    qty: Number(formData.get("qty")),
    note: String(formData.get("note") ?? "") || undefined,
  };
  await runMutation(`/portal/inventaris/${itemId}`, async () => {
    await serverApi(`/inventory-items/${itemId}/transactions`, { method: "POST", body: payload });
    revalidatePath(`/portal/inventaris/${itemId}`);
  });
}
