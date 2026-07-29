"use client";

import { useActionState } from "react";
import { createInventoryItemAction, updateInventoryItemAction } from "@/lib/actions/inventory";
import { Input, Select } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { Alert } from "@/components/ui/Feedback";

type InventoryItem = {
  id: number;
  branch_id: number;
  name: string;
  category: string;
  stock_qty: number;
  condition: string | null;
};

export function InventoryItemForm({ item, defaultBranchId }: { item?: InventoryItem; defaultBranchId?: number }) {
  const action = item ? updateInventoryItemAction.bind(null, item.id) : createInventoryItemAction;
  const [state, formAction, pending] = useActionState(action, undefined);
  const fieldErrors = state?.fieldErrors ?? {};

  return (
    <form action={formAction} className="flex flex-col gap-4">
      {state?.error && <Alert tone="crit">{state.error}</Alert>}
      {!item && <input type="hidden" name="branch_id" value={defaultBranchId ?? ""} />}
      <Input label="Nama barang" name="name" required defaultValue={item?.name} error={fieldErrors.name} />
      <Input label="Kategori" name="category" required defaultValue={item?.category} error={fieldErrors.category} placeholder="mis. raket, bola, net" />
      {!item && <Input label="Stok awal" name="stock_qty" type="number" min={0} defaultValue={0} />}
      <Select label="Kondisi" name="condition" defaultValue={item?.condition ?? "good"}>
        <option value="good">Baik</option>
        <option value="damaged">Rusak</option>
      </Select>
      <Button type="submit" loading={pending} className="self-end">
        {item ? "Simpan Perubahan" : "Tambah Barang"}
      </Button>
    </form>
  );
}
