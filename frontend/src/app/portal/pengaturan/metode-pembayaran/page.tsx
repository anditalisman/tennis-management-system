import Link from "next/link";
import { verifySession } from "@/lib/dal";
import { serverApi } from "@/lib/server-api";
import { Card, CardHeader } from "@/components/ui/Card";
import { Table, Thead, Tbody, Tr, Th, Td } from "@/components/ui/Table";
import { StatusBadge } from "@/components/ui/Badge";
import { EmptyState } from "@/components/ui/Feedback";
import { Button } from "@/components/ui/Button";
import { deletePaymentMethodAction } from "@/lib/actions/payment-methods";

export const metadata = { title: "Metode Pembayaran" };

const TYPE_LABELS: Record<string, string> = {
  qris: "QRIS",
  bank_transfer: "Transfer Bank",
  cash: "Tunai",
  other: "Lainnya",
};

type PaymentMethod = {
  id: number;
  type: string;
  label: string;
  details: string | null;
  image_url: string | null;
  is_active: boolean;
};

export default async function MetodePembayaranListPage() {
  await verifySession();

  const methods = await serverApi<PaymentMethod[]>("/payment-methods");

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Metode Pembayaran</h1>
          <p className="mt-1 text-sm text-(--color-ink-500)">
            Metode yang ditampilkan ke peserta saat membayar tagihan, termasuk gambar QRIS.
          </p>
        </div>
        <Link href="/portal/pengaturan/metode-pembayaran/baru">
          <Button>Tambah Metode</Button>
        </Link>
      </div>

      <Card className="mt-6">
        {methods.length === 0 ? (
          <div className="p-6">
            <EmptyState title="Belum ada metode pembayaran" description="Tambahkan QRIS atau rekening bank agar peserta bisa membayar." />
          </div>
        ) : (
          <>
            <CardHeader title={`${methods.length} Metode`} />
            <div className="p-4">
              <Table>
                <Thead>
                  <Tr>
                    <Th></Th>
                    <Th>Nama</Th>
                    <Th>Jenis</Th>
                    <Th>Status</Th>
                    <Th></Th>
                  </Tr>
                </Thead>
                <Tbody>
                  {methods.map((m) => (
                    <Tr key={m.id}>
                      <Td>
                        {m.image_url ? (
                          // eslint-disable-next-line @next/next/no-img-element
                          <img src={m.image_url} alt={m.label} className="h-10 w-10 rounded-lg border border-(--color-ink-900)/10 object-contain" />
                        ) : (
                          <div className="h-10 w-10 rounded-lg border border-dashed border-(--color-ink-900)/15" />
                        )}
                      </Td>
                      <Td className="font-semibold">{m.label}</Td>
                      <Td>{TYPE_LABELS[m.type] ?? m.type}</Td>
                      <Td>
                        <StatusBadge status={m.is_active ? "active" : "inactive"} label={m.is_active ? "Aktif" : "Nonaktif"} />
                      </Td>
                      <Td>
                        <div className="flex items-center gap-3">
                          <Link
                            href={`/portal/pengaturan/metode-pembayaran/${m.id}/edit`}
                            className="font-semibold text-(--color-court-600) hover:underline"
                          >
                            Edit
                          </Link>
                          <form action={deletePaymentMethodAction.bind(null, m.id)}>
                            <button type="submit" className="font-semibold text-(--color-crit) hover:underline">
                              Hapus
                            </button>
                          </form>
                        </div>
                      </Td>
                    </Tr>
                  ))}
                </Tbody>
              </Table>
            </div>
          </>
        )}
      </Card>
    </div>
  );
}
