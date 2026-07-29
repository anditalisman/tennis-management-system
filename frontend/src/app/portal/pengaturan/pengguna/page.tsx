import Link from "next/link";
import { verifySession } from "@/lib/dal";
import { serverApiPaginated } from "@/lib/server-api";
import { ROLE_LABELS } from "@/lib/roles";
import { Card, CardHeader } from "@/components/ui/Card";
import { Table, Thead, Tbody, Tr, Th, Td } from "@/components/ui/Table";
import { Pagination } from "@/components/ui/Pagination";
import { StatusBadge } from "@/components/ui/Badge";
import { EmptyState } from "@/components/ui/Feedback";
import { Button } from "@/components/ui/Button";
import { deactivateUserAction } from "@/lib/actions/users";

export const metadata = { title: "Pengguna & Peran" };

type User = { id: string; name: string; email: string; roles: string[]; status: string };

export default async function PenggunaListPage({ searchParams }: { searchParams: Promise<{ page?: string }> }) {
  await verifySession();
  const { page = "1" } = await searchParams;

  const users = await serverApiPaginated<User>(`/users?page=${page}&per_page=15`);

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="font-display text-2xl font-bold text-(--color-ink-900)">Pengguna & Peran</h1>
          <p className="mt-1 text-sm text-(--color-ink-500)">Kelola akun staf dan peran akses.</p>
        </div>
        <Link href="/portal/pengaturan/pengguna/baru">
          <Button>Tambah Pengguna</Button>
        </Link>
      </div>

      <Card className="mt-6">
        {users.data.length === 0 ? (
          <div className="p-6">
            <EmptyState title="Belum ada pengguna" />
          </div>
        ) : (
          <>
            <CardHeader title={`${users.meta.total} Pengguna`} />
            <div className="p-4">
              <Table>
                <Thead>
                  <Tr>
                    <Th>Nama</Th>
                    <Th>Email</Th>
                    <Th>Peran</Th>
                    <Th>Status</Th>
                    <Th></Th>
                  </Tr>
                </Thead>
                <Tbody>
                  {users.data.map((u) => (
                    <Tr key={u.id}>
                      <Td className="font-semibold">{u.name}</Td>
                      <Td>{u.email}</Td>
                      <Td>{u.roles.map((r) => ROLE_LABELS[r] ?? r).join(", ")}</Td>
                      <Td>
                        <StatusBadge status={u.status} />
                      </Td>
                      <Td>
                        <div className="flex items-center gap-3">
                          <Link href={`/portal/pengaturan/pengguna/${u.id}/edit`} className="font-semibold text-(--color-court-600) hover:underline">
                            Edit
                          </Link>
                          <form action={deactivateUserAction.bind(null, u.id)}>
                            <button type="submit" className="font-semibold text-(--color-crit) hover:underline">
                              Nonaktifkan
                            </button>
                          </form>
                        </div>
                      </Td>
                    </Tr>
                  ))}
                </Tbody>
              </Table>
              <Pagination currentPage={users.meta.current_page} lastPage={users.meta.last_page} makeHref={(p) => `/portal/pengaturan/pengguna?page=${p}`} />
            </div>
          </>
        )}
      </Card>
    </div>
  );
}
