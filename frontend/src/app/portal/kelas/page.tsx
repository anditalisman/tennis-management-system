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

export const metadata = { title: "Kelas" };

type TrainingClass = {
  id: number;
  name: string;
  active_member_count: number;
  capacity_max: number;
  quota_remaining: number;
  status: string;
};

export default async function KelasListPage({ searchParams }: { searchParams: Promise<{ page?: string }> }) {
  const session = await verifySession();
  const { page = "1" } = await searchParams;
  const staff = isStaff(session.user.roles);

  const classes = await serverApiPaginated<TrainingClass>(`/classes?page=${page}&per_page=15`);

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Kelas</h1>
          <p className="mt-1 text-sm text-(--color-ink-500)">Kelola kelas dan keanggotaan peserta.</p>
        </div>
        {staff && (
          <Link href="/portal/kelas/baru">
            <Button>Tambah Kelas</Button>
          </Link>
        )}
      </div>

      <Card className="mt-6">
        {classes.data.length === 0 ? (
          <div className="p-6">
            <EmptyState title="Belum ada kelas" />
          </div>
        ) : (
          <>
            <CardHeader title={`${classes.meta.total} Kelas`} />
            <div className="p-4">
              <Table>
                <Thead>
                  <Tr>
                    <Th>Nama Kelas</Th>
                    <Th>Anggota</Th>
                    <Th>Sisa Kuota</Th>
                    <Th>Status</Th>
                    <Th></Th>
                  </Tr>
                </Thead>
                <Tbody>
                  {classes.data.map((c) => (
                    <Tr key={c.id}>
                      <Td className="font-semibold">{c.name}</Td>
                      <Td>
                        {c.active_member_count}/{c.capacity_max}
                      </Td>
                      <Td>{c.quota_remaining}</Td>
                      <Td>
                        <StatusBadge status={c.status} />
                      </Td>
                      <Td>
                        <Link href={`/portal/kelas/${c.id}`} className="font-semibold text-(--color-court-600) hover:underline">
                          Detail
                        </Link>
                      </Td>
                    </Tr>
                  ))}
                </Tbody>
              </Table>
              <Pagination currentPage={classes.meta.current_page} lastPage={classes.meta.last_page} makeHref={(p) => `/portal/kelas?page=${p}`} />
            </div>
          </>
        )}
      </Card>
    </div>
  );
}
