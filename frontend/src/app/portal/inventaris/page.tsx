import Link from "next/link";
import { verifySession } from "@/lib/dal";
import { serverApiPaginated } from "@/lib/server-api";
import { isStaff } from "@/lib/roles";
import { Card, CardHeader } from "@/components/ui/Card";
import { Table, Thead, Tbody, Tr, Th, Td } from "@/components/ui/Table";
import { Pagination } from "@/components/ui/Pagination";
import { Badge } from "@/components/ui/Badge";
import { EmptyState } from "@/components/ui/Feedback";
import { Button } from "@/components/ui/Button";

export const metadata = { title: "Inventaris" };

type InventoryItem = {
  id: number;
  name: string;
  category: string;
  stock_qty: number;
  condition: string | null;
};

export default async function InventarisListPage({ searchParams }: { searchParams: Promise<{ page?: string }> }) {
  const session = await verifySession();
  const { page = "1" } = await searchParams;
  const staff = isStaff(session.user.roles);

  const items = await serverApiPaginated<InventoryItem>(`/inventory-items?page=${page}&per_page=15`);

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Inventaris</h1>
          <p className="mt-1 text-sm text-(--color-ink-500)">Kelola peralatan dan perlengkapan latihan.</p>
        </div>
        {staff && (
          <Link href="/portal/inventaris/baru">
            <Button>Tambah Barang</Button>
          </Link>
        )}
      </div>

      <Card className="mt-6">
        {items.data.length === 0 ? (
          <div className="p-6">
            <EmptyState title="Belum ada barang inventaris" />
          </div>
        ) : (
          <>
            <CardHeader title={`${items.meta.total} Barang`} />
            <div className="p-4">
              <Table>
                <Thead>
                  <Tr>
                    <Th>Nama</Th>
                    <Th>Kategori</Th>
                    <Th>Stok</Th>
                    <Th>Kondisi</Th>
                    <Th></Th>
                  </Tr>
                </Thead>
                <Tbody>
                  {items.data.map((item) => (
                    <Tr key={item.id}>
                      <Td className="font-semibold">{item.name}</Td>
                      <Td className="capitalize">{item.category}</Td>
                      <Td>{item.stock_qty}</Td>
                      <Td>
                        <Badge tone={item.condition === "damaged" ? "crit" : "good"}>{item.condition === "damaged" ? "Rusak" : "Baik"}</Badge>
                      </Td>
                      <Td>
                        <Link href={`/portal/inventaris/${item.id}`} className="font-semibold text-(--color-court-600) hover:underline">
                          Detail
                        </Link>
                      </Td>
                    </Tr>
                  ))}
                </Tbody>
              </Table>
              <Pagination
                currentPage={items.meta.current_page}
                lastPage={items.meta.last_page}
                makeHref={(p) => `/portal/inventaris?page=${p}`}
              />
            </div>
          </>
        )}
      </Card>
    </div>
  );
}
