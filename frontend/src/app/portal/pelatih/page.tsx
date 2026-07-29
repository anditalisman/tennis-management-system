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
import { deleteCoachAction } from "@/lib/actions/coaches";

export const metadata = { title: "Pelatih" };

type Coach = {
  id: number;
  name: string | null;
  email: string | null;
  employment_status: string;
};

export default async function PelatihListPage({ searchParams }: { searchParams: Promise<{ page?: string }> }) {
  const session = await verifySession();
  const { page = "1" } = await searchParams;
  const staff = isStaff(session.user.roles);

  const coaches = await serverApiPaginated<Coach>(`/coaches?page=${page}&per_page=15`);

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Pelatih</h1>
          <p className="mt-1 text-sm text-(--color-ink-500)">Kelola data pelatih dan akun mereka.</p>
        </div>
        {staff && (
          <Link href="/portal/pelatih/baru">
            <Button>Tambah Pelatih</Button>
          </Link>
        )}
      </div>

      <Card className="mt-6">
        {coaches.data.length === 0 ? (
          <div className="p-6">
            <EmptyState title="Belum ada data pelatih" />
          </div>
        ) : (
          <>
            <CardHeader title={`${coaches.meta.total} Pelatih`} />
            <div className="p-4">
              <Table>
                <Thead>
                  <Tr>
                    <Th>Nama</Th>
                    <Th>Email</Th>
                    <Th>Status</Th>
                    {staff && <Th></Th>}
                  </Tr>
                </Thead>
                <Tbody>
                  {coaches.data.map((c) => (
                    <Tr key={c.id}>
                      <Td className="font-semibold">{c.name}</Td>
                      <Td>{c.email}</Td>
                      <Td>
                        <StatusBadge status={c.employment_status} />
                      </Td>
                      {staff && (
                        <Td>
                          <div className="flex items-center gap-3">
                            <Link href={`/portal/pelatih/${c.id}/edit`} className="font-semibold text-(--color-court-600) hover:underline">
                              Edit
                            </Link>
                            <form action={deleteCoachAction.bind(null, c.id)}>
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
              <Pagination currentPage={coaches.meta.current_page} lastPage={coaches.meta.last_page} makeHref={(p) => `/portal/pelatih?page=${p}`} />
            </div>
          </>
        )}
      </Card>
    </div>
  );
}
