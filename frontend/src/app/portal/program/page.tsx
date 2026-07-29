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
import { deleteProgramAction } from "@/lib/actions/programs";

export const metadata = { title: "Program" };

type Program = {
  id: number;
  name: string;
  age_group: string | null;
  skill_level: string | null;
  status: string;
};

export default async function ProgramListPage({ searchParams }: { searchParams: Promise<{ page?: string }> }) {
  const session = await verifySession();
  const { page = "1" } = await searchParams;
  const staff = isStaff(session.user.roles);

  const programs = await serverApiPaginated<Program>(`/programs?page=${page}&per_page=15`);

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Program</h1>
          <p className="mt-1 text-sm text-(--color-ink-500)">Kelola program latihan yang ditawarkan.</p>
        </div>
        {staff && (
          <Link href="/portal/program/baru">
            <Button>Tambah Program</Button>
          </Link>
        )}
      </div>

      <Card className="mt-6">
        {programs.data.length === 0 ? (
          <div className="p-6">
            <EmptyState title="Belum ada program" />
          </div>
        ) : (
          <>
            <CardHeader title={`${programs.meta.total} Program`} />
            <div className="p-4">
              <Table>
                <Thead>
                  <Tr>
                    <Th>Nama</Th>
                    <Th>Usia</Th>
                    <Th>Level</Th>
                    <Th>Status</Th>
                    {staff && <Th></Th>}
                  </Tr>
                </Thead>
                <Tbody>
                  {programs.data.map((p) => (
                    <Tr key={p.id}>
                      <Td className="font-semibold">{p.name}</Td>
                      <Td className="capitalize">{p.age_group ?? "-"}</Td>
                      <Td className="capitalize">{p.skill_level ?? "-"}</Td>
                      <Td>
                        <StatusBadge status={p.status} />
                      </Td>
                      {staff && (
                        <Td>
                          <div className="flex items-center gap-3">
                            <Link href={`/portal/program/${p.id}/edit`} className="font-semibold text-(--color-court-600) hover:underline">
                              Edit
                            </Link>
                            <form action={deleteProgramAction.bind(null, p.id)}>
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
              <Pagination currentPage={programs.meta.current_page} lastPage={programs.meta.last_page} makeHref={(p) => `/portal/program?page=${p}`} />
            </div>
          </>
        )}
      </Card>
    </div>
  );
}
