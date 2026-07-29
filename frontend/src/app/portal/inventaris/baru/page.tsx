import { verifySession } from "@/lib/dal";
import { getDefaultBranchId } from "@/lib/branch";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { InventoryItemForm } from "@/components/portal/InventoryItemForm";

export const metadata = { title: "Tambah Barang" };

export default async function NewInventoryItemPage() {
  await verifySession();
  const defaultBranchId = await getDefaultBranchId();

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Tambah Barang Inventaris</h1>
      <Card className="mt-6">
        <CardHeader title="Detail Barang" />
        <CardBody>
          <InventoryItemForm defaultBranchId={defaultBranchId} />
        </CardBody>
      </Card>
    </div>
  );
}
