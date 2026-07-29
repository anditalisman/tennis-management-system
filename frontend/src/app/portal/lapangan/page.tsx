import Link from "next/link";
import { verifySession } from "@/lib/dal";
import { serverApiPaginated } from "@/lib/server-api";
import { isStaff } from "@/lib/roles";
import { Card, CardHeader } from "@/components/ui/Card";
import { Table, Thead, Tbody, Tr, Th, Td } from "@/components/ui/Table";
import { Pagination } from "@/components/ui/Pagination";
import { StatusBadge } from "@/components/ui/Badge";
import { EmptyState } from "@/components/ui/Feedback";
import { Button } from "@/components/ui/Button";
import { formatCurrency } from "@/lib/format";
import { deleteCourtAction } from "@/lib/actions/courts";

export const metadata = { title: "Lapangan" };

type Court = {
  id: number;
  name: string;
  surface_type: string | null;
  rental_cost: number | null;
  status: string;
};

export default async function LapanganListPage({ searchParams }: { searchParams: Promise<{ page?: string }> }) {
  const session = await verifySession();
  const { page = "1" } = await searchParams;
  const staff = isStaff(session.user.roles);

  const courts = await serverApiPaginated<Court>(`/courts?page=${page}&per_page=15`);

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Lapangan</h1>
          <p className="mt-1 text-sm text-(--color-ink-500)">Kelola fasilitas lapangan setiap cabang.</p>
        </div>
        {staff && (
          <Link href="/portal/lapangan/baru">
            <Button>Tambah Lapangan</Button>
          </Link>
        )}
      </div>

      <Card className="mt-6">
        {courts.data.length === 0 ? (
          <div className="p-6">
            <EmptyState title="Belum ada lapangan" />
          </div>
        ) : (
          <>
            <CardHeader title={`${courts.meta.total} Lapangan`} />
            <div className="p-4">
              <Table>
                <Thead>
                  <Tr>
                    <Th>Nama</Th>
                    <Th>Permukaan</Th>
                    <Th>Biaya Sewa</Th>
                    <Th>Status</Th>
                    {staff && <Th></Th>}
                  </Tr>
                </Thead>
                <Tbody>
                  {courts.data.map((c) => (
                    <Tr key={c.id}>
                      <Td className="font-semibold">{c.name}</Td>
                      <Td className="capitalize">{c.surface_type ?? "-"}</Td>
                      <Td>{c.rental_cost ? formatCurrency(c.rental_cost) : "-"}</Td>
                      <Td>
                        <StatusBadge status={c.status} />
                      </Td>
                      {staff && (
                        <Td>
                          <div className="flex items-center gap-3">
                            <Link href={`/portal/lapangan/${c.id}/edit`} className="font-semibold text-(--color-court-600) hover:underline">
                              Edit
                            </Link>
                            <form action={deleteCourtAction.bind(null, c.id)}>
                              <button type="submit" className="font-semibold text-(--color-crit) hover:underline">
                                Hapus
                              </button>
                            </form>
                          </div>
                        </Td>
                      )}
                    </Tr>
                  ))}
                </Tbody>
              </Table>
              <Pagination currentPage={courts.meta.current_page} lastPage={courts.meta.last_page} makeHref={(p) => `/portal/lapangan?page=${p}`} />
            </div>
          </>
        )}
      </Card>
    </div>
  );
}
