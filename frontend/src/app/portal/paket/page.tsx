import Link from "next/link";
import { verifySession } from "@/lib/dal";
import { serverApiPaginated } from "@/lib/server-api";
import { hasRole, ROLES } from "@/lib/roles";
import { Card, CardHeader } from "@/components/ui/Card";
import { Table, Thead, Tbody, Tr, Th, Td } from "@/components/ui/Table";
import { Pagination } from "@/components/ui/Pagination";
import { StatusBadge } from "@/components/ui/Badge";
import { EmptyState } from "@/components/ui/Feedback";
import { Button } from "@/components/ui/Button";
import { formatCurrency } from "@/lib/format";
import { deletePackageAction } from "@/lib/actions/packages";

export const metadata = { title: "Paket" };

type Package = {
  id: number;
  program_id: number;
  name: string;
  session_count: number;
  price: number;
  status: string;
};

export default async function PaketListPage({ searchParams }: { searchParams: Promise<{ page?: string }> }) {
  const session = await verifySession();
  const { page = "1" } = await searchParams;
  const canManage = hasRole(session.user.roles, ROLES.SUPER_ADMIN, ROLES.ADMINISTRATOR);

  const packages = await serverApiPaginated<Package>(`/packages?page=${page}&per_page=15`);

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Paket</h1>
          <p className="mt-1 text-sm text-(--color-ink-500)">Paket latihan yang tersedia untuk dibeli peserta.</p>
        </div>
        {canManage && (
          <Link href="/portal/paket/baru">
            <Button>Tambah Paket</Button>
          </Link>
        )}
      </div>

      <Card className="mt-6">
        {packages.data.length === 0 ? (
          <div className="p-6">
            <EmptyState title="Belum ada paket" />
          </div>
        ) : (
          <>
            <CardHeader title={`${packages.meta.total} Paket`} />
            <div className="p-4">
              <Table>
                <Thead>
                  <Tr>
                    <Th>Nama</Th>
                    <Th>Sesi</Th>
                    <Th>Harga</Th>
                    <Th>Status</Th>
                    {canManage && <Th></Th>}
                  </Tr>
                </Thead>
                <Tbody>
                  {packages.data.map((p) => (
                    <Tr key={p.id}>
                      <Td className="font-semibold">{p.name}</Td>
                      <Td>{p.session_count} sesi</Td>
                      <Td>{formatCurrency(p.price)}</Td>
                      <Td>
                        <StatusBadge status={p.status} />
                      </Td>
                      {canManage && (
                        <Td>
                          <div className="flex items-center gap-3">
                            <Link href={`/portal/paket/${p.id}/edit`} className="font-semibold text-(--color-court-600) hover:underline">
                              Edit
                            </Link>
                            <form action={deletePackageAction.bind(null, p.id)}>
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
              <Pagination currentPage={packages.meta.current_page} lastPage={packages.meta.last_page} makeHref={(p) => `/portal/paket?page=${p}`} />
            </div>
          </>
        )}
      </Card>
    </div>
  );
}
