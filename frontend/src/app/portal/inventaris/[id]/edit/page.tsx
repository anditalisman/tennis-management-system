import { notFound } from "next/navigation";
import { verifySession } from "@/lib/dal";
import { serverApiOrNull } from "@/lib/server-api";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { InventoryItemForm } from "@/components/portal/InventoryItemForm";

export const metadata = { title: "Edit Barang" };

type InventoryItem = {
  id: number;
  branch_id: number;
  name: string;
  category: string;
  stock_qty: number;
  condition: string | null;
};

export default async function EditInventoryItemPage({ params }: { params: Promise<{ id: string }> }) {
  await verifySession();
  const { id } = await params;

  const item = await serverApiOrNull<InventoryItem>(`/inventory-items/${id}`);
  if (!item) notFound();

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Edit Barang</h1>
      <Card className="mt-6">
        <CardHeader title={item.name} />
        <CardBody>
          <InventoryItemForm item={item} />
        </CardBody>
      </Card>
    </div>
  );
}
