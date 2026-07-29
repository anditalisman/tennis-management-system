import Link from "next/link";
import { notFound } from "next/navigation";
import { verifySession } from "@/lib/dal";
import { serverApiOrNull } from "@/lib/server-api";
import { isStaff } from "@/lib/roles";
import { Card, CardBody, CardHeader } from "@/components/ui/Card";
import { Badge } from "@/components/ui/Badge";
import { Alert, EmptyState } from "@/components/ui/Feedback";
import { Input, Select } from "@/components/ui/Field";
import { Button } from "@/components/ui/Button";
import { formatDateTime } from "@/lib/format";
import { createTransactionAction } from "@/lib/actions/inventory";

export const metadata = { title: "Detail Barang" };

type InventoryItem = {
  id: number;
  name: string;
  category: string;
  stock_qty: number;
  condition: string | null;
};
type Transaction = {
  id: number;
  type: string;
  qty: number;
  created_by: string | null;
  note: string | null;
  created_at: string;
};

const TYPE_LABELS: Record<string, string> = {
  in: "Masuk",
  out: "Keluar",
  borrow: "Dipinjam",
  return: "Dikembalikan",
  damage: "Rusak",
  loss: "Hilang",
};

export default async function InventoryDetailPage({
  params,
  searchParams,
}: {
  params: Promise<{ id: string }>;
  searchParams: Promise<{ error?: string }>;
}) {
  const session = await verifySession();
  const { id } = await params;
  const { error } = await searchParams;
  const staff = isStaff(session.user.roles);

  const item = await serverApiOrNull<InventoryItem>(`/inventory-items/${id}`);
  if (!item) notFound();

  const transactions = (await serverApiOrNull<Transaction[]>(`/inventory-items/${id}/transactions`)) ?? [];

  return (
    <div className="max-w-3xl">
      <Link href="/portal/inventaris" className="text-sm font-semibold text-(--color-court-600) hover:underline">
        ← Kembali ke daftar inventaris
      </Link>

      {error && (
        <div className="mt-4">
          <Alert tone="crit">{error}</Alert>
        </div>
      )}

      <div className="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">{item.name}</h1>
          <p className="mt-1 text-sm text-(--color-ink-500)">
            {item.category} · Stok saat ini: {item.stock_qty}
          </p>
        </div>
        <div className="flex items-center gap-3">
          <Badge tone={item.condition === "damaged" ? "crit" : "good"}>{item.condition === "damaged" ? "Rusak" : "Baik"}</Badge>
          {staff && (
            <Link href={`/portal/inventaris/${id}/edit`} className="text-sm font-semibold text-(--color-court-600) hover:underline">
              Edit
            </Link>
          )}
        </div>
      </div>

      {staff && (
        <Card className="mt-6">
          <CardHeader title="Catat Transaksi" />
          <CardBody>
            <form action={createTransactionAction.bind(null, item.id)} className="grid grid-cols-1 gap-4 sm:grid-cols-4 sm:items-end">
              <Select label="Jenis" name="type" required defaultValue="in">
                {Object.entries(TYPE_LABELS).map(([value, label]) => (
                  <option key={value} value={value}>
                    {label}
                  </option>
                ))}
              </Select>
              <Input label="Jumlah" name="qty" type="number" min={1} required defaultValue={1} />
              <div className="sm:col-span-2">
                <Input label="Catatan (opsional)" name="note" />
              </div>
              <Button type="submit" className="sm:col-span-4 sm:self-end sm:justify-self-end">
                Simpan Transaksi
              </Button>
            </form>
          </CardBody>
        </Card>
      )}

      <Card className="mt-6">
        <CardHeader title="Riwayat Transaksi" />
        <CardBody>
          {transactions.length === 0 ? (
            <EmptyState title="Belum ada transaksi" />
          ) : (
            <ul className="flex flex-col divide-y divide-(--color-ink-900)/8">
              {transactions.map((tx) => (
                <li key={tx.id} className="flex items-center justify-between py-3 text-sm first:pt-0 last:pb-0">
                  <div>
                    <p className="font-semibold text-(--color-ink-900)">
                      {TYPE_LABELS[tx.type] ?? tx.type} — {tx.qty}
                    </p>
                    <p className="text-(--color-ink-500)">
                      {formatDateTime(tx.created_at)} {tx.created_by ? `· ${tx.created_by}` : ""} {tx.note ? `· ${tx.note}` : ""}
                    </p>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </CardBody>
      </Card>
    </div>
  );
}
